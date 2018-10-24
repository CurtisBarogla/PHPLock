<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Lockey component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
 
namespace Ness\Component\Lockey;

use Ness\Component\User\UserInterface;
use Ness\Component\Lockey\Exception\LockTokenCorruptedException;

/**
 * Lock token are made to keep a trace of lock interaction over a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class LockToken implements \JsonSerializable
{
    
    /**
     * Resource name which the token is linked
     * 
     * @var string
     */
    private $resource;
    
    /**
     * A list a usernames which represent all users whose has access to the resource
     * Master user is always setted in first
     * 
     * @var string[]
     */
    private $users;
    
    /**
     * Type of token.
     * 
     * @var int
     */
    private $type;
    
    /**
     * Immutable state
     * 
     * @var bool
     */
    private $immutable = false;
    
    /**
     * Lock token validity
     * 
     * @var int
     */
    private $validity;
    
    /**
     * Master index among users list
     * 
     * @var string
     */
    private const USER_MASTER_INDEX = "master";
    
    /**
     * Shared users index among users list
     * 
     * @var string
     */
    private const USER_SHARE_INDEX = "share";
    
    /**
     * Exclusive token. Only accessible to the master
     * 
     * @var int
     */
    public const EXCLUSIVE = 0;
    
    /**
     * Restreint share token. Only fully accessible to the master and only in read for the shared users
     * 
     * @var int
     */
    public const SHARE = 1;
    
    /**
     * Token is fully shared among the master and all shared users
     * 
     * @var int
     */
    public const FULL = 2;
    
    /**
     * Initialize a new lock token
     * 
     * @param string $resource
     *   Resource name
     * @param int $type
     *   Type of lock token (no defensive checking here... lock token should never be instantiate by other than the Locker)
     */
    public function __construct(string $resource, int $type)
    {
        $this->resource = $resource;
        $this->type = $type;
    }
    
    /**
     * Set lock token validity
     * 
     * @param \DateInterval $validity
     *   Interval which the token is valid
     *   
     * @throws \LogicException
     *   When the token is in an immutable state
     */
    public function setValidity(\DateInterval $validity): void
    {
        $this->checkImmutable();
        
        $this->validity = (new \DateTimeImmutable())->add($validity)->getTimestamp();
    }
    
    /**
     * Get the lock token validity duration
     * 
     * @return \DateTimeImmutable
     *   Lock token validity duration
     */
    public function getValidity(): \DateTimeImmutable
    {
        if($this->validity instanceof \DateTimeImmutable)
            return $this->validity;
        
        return $this->validity = new \DateTimeImmutable("@{$this->validity}");
    }
    
    /**
     * Get the resource name which the token is linked
     * 
     * @return string
     *   Resource name
     */
    public function getResource(): string
    {
        return $this->resource;
    }
    
    /**
     * Get type attributed to the token
     * 
     * @return int
     *   Token type
     */
    public function getType(): int
    {
        return $this->type;
    }
    
    /**
     * Set the master
     * 
     * @param UserInterface $user
     *   User master
     * 
     * @throws \LogicException
     *   When the token is in an immutable state
     */
    public function setMaster(UserInterface $user): void
    {   
        $this->checkImmutable();
        
        $this->users[self::USER_MASTER_INDEX] = $user->getName();
    }
    
    /**
     * Get the master assigned to this lock token
     * 
     * @return string
     *   User master name
     */
    public function getMaster(): string
    {
        return $this->users[self::USER_MASTER_INDEX];
    }
    
    /**
     * Check if the given user is master of the lock token 
     * 
     * @param UserInterface $user
     *   User to check
     * 
     * @return bool
     *   True if the given user is master of the lock token. False otherwise
     */
    public function isMaster(UserInterface $user): bool
    {
        return $this->users[self::USER_MASTER_INDEX] === $user->getName();
    }
    
    /**
     * Share the lock token with the given user
     * 
     * @param UserInterface $user
     *   User with to share the lock token
     *   
     * @throws \LogicException
     *   When master user is not setted
     * @throws \LogicException
     *   When the lock token is not into one of the share mode
     * @throws \LogicException
     *   When the token is in an immutable state
     */
    public function shareWith(UserInterface $user): void
    {
        $this->checkImmutable();
        
        if(null === $this->users[self::USER_MASTER_INDEX])
            throw new \LogicException("No master has been defined for this lock token. Therefore no user can been added to the share list");
        
        if($this->type === self::EXCLUSIVE)
            throw new \LogicException("This token cannot be shared as it is not declared as if");
        
        if(\in_array(($name = $user->getName()), $this->users[self::USER_SHARE_INDEX] ?? []))
            return;
        
        $this->users[self::USER_SHARE_INDEX][] = $name;
    }
    
    /**
     * Check if the user is in the shared list
     * 
     * @param UserInterface $user
     *   User to check
     * 
     * @return bool
     *   True if the user shares the lock token. False otherwise
     */
    public function isSharedWith(UserInterface $user): bool
    {
        return ($this->type !== self::EXCLUSIVE) ? \in_array($user->getName(), $this->users[self::USER_SHARE_INDEX] ?? []) : false;
    }
    
    /**
     * Make a copy of the given token for a new resource
     * 
     * @param string $resource
     *   Resource which to copy the token
     * @param LockToken $token
     *   Token to replicate
     * 
     * @return LockToken
     *   Copy of the given token for the given resource
     */
    public static function copy(string $resource, LockToken $token): LockToken
    {
        $copy = new self($resource, $token->type);
        $copy->validity = $token->validity;
        $copy->users = $token->users;
        
        return $copy;
    }
    
    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            $this->resource,
            $this->type,
            $this->users,
            $this->getValidity()->getTimestamp()
        ];
    }
    
    /**
     * Initialize a lock token from is json representation.
     * Lock token restored is in an immutable state
     * 
     * @param string $json
     *   Json lock token representation
     *   
     * @return LockToken
     *   Lock token restored
     *   
     * @throws LockTokenCorruptedException
     *   When the lock token cannot be restored
     */
    public static function createFromJson(string $json): LockToken
    {
        $json = \json_decode($json, true);
        
        if(null === $json)
            throw new LockTokenCorruptedException("Token cannot be restored from his json representation");
        
        $token = new self($json[0], $json[1]);
        $token->users = $json[2];
        $token->immutable = true;
        $token->validity = new \DateTimeImmutable("@{$json[3]}");
        
        return $token;
    }
    
    /**
     * Check if the lock token is in an immutable state
     * 
     * @throws \LogicException
     *   If the lock token is immutable
     */
    private function checkImmutable(): void
    {
        if($this->immutable)
            throw new \LogicException("This lock token for resource '{$this->resource}' is in an immutable state and therefore cannot be updated");
    }

}
