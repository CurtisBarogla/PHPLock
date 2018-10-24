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

/**
 * Represents informations about a lock over a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class LockState
{
    
    /**
     * Resource which state is about
     * 
     * @var LockableResourceInterface
     */
    private $resource;
    
    /**
     * Lock token attributed to this state
     * 
     * @var LockToken|null
     */
    private $token;
    
    /**
     * Current user
     * 
     * @var UserInterface
     */
    private $user;
    
    /**
     * Initialize a new lock state
     * 
     * @param LockableResourceInterface $resource
     *   Resource which this state is about
     * @param LockToken|null $token
     *   A potential token attributed to the resource
     * @param UserInterface $user
     *   User to check
     */
    public function __construct(LockableResourceInterface $resource, ?LockToken $token, UserInterface $user)
    {
        $this->resource = $resource;
        $this->token = $token;
        $this->user = $user;
    }
    
    /**
     * Get the resource checked by this state
     * 
     * @return LockableResourceInterface
     *   Lockable resource
     */
    public function getResource(): LockableResourceInterface
    {
        return $this->resource;
    }
    
    /**
     * Check if a lock has been attribute to the given resource
     * 
     * @return bool
     *   True if a lock is found. False otherwise
     */
    public function isLocked(): bool
    {
        return null !== $this->token;
    }
    
    /**
     * Check if the resource is currently accessible to the suer
     * 
     * @return bool
     *   True if the resource is accessible. False otherwise
     */
    public function isAccessible(): bool
    {
        if(null === $this->token)
            return true;

        return ($this->token->getType() === LockToken::EXCLUSIVE) ? $this->token->isMaster($this->user) : $this->token->isMaster($this->user) || $this->token->isSharedWith($this->user);
    }
    
    /**
     * Get the lock token attribute to this state
     * 
     * @return LockToken|null
     *   Lock token attributed to this state or null if no token found
     */
    public function getToken(): ?LockToken
    {
        return $this->token;
    }
    
}
