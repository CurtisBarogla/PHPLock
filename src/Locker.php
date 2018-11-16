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
use Ness\Component\Lockey\Storage\LockTokenPoolInterface;
use Ness\Component\Lockey\Exception\TokenPoolTransactionErrorException;
use Ness\Component\Lockey\Exception\LockErrorException;
use Ness\Component\Lockey\Exception\LockTokenExpiredException;
use Ness\Component\Lockey\Exception\UnlockErrorException;

/**
 * Simple implementation of LockerInterface.
 * Based on a LockTokenPool which provide the transaction mechanism
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Locker implements LockerInterface
{
    
    /**
     * Lock token pool
     * 
     * @var LockTokenPoolInterface
     */
    private $pool;
    
    /**
     * Initialize locker
     * 
     * @param LockTokenPoolInterface $pool
     *   Lock token pool
     */
    public function __construct(LockTokenPoolInterface $pool)
    {
        $this->pool = $pool;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockerInterface::getState()
     */
    public function getState(UserInterface $user, LockableResourceInterface $resource): LockState
    {
        return new LockState($resource, $this->pool->getToken($resource), $user);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockerInterface::exclusive()
     */
    public function exclusive(UserInterface $user, LockableResourceInterface $resource, \DateInterval $duration): void
    {
        if(null !== $this->pool->getToken($resource))
            return;
        
        $this->acquireLock(self::generateToken($resource->getLockableName(), $user, $duration), $resource);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockerInterface::share()
     */
    public function share(UserInterface $user, array $users, LockableResourceInterface $resource, \DateInterval $duration, bool $full = false): void
    {
        if(null !== $this->pool->getToken($resource))
            return;
        
        $this->acquireLock(self::generateToken($resource->getLockableName(), $user, $duration, $users, $full ? LockToken::FULL : LockToken::SHARE), $resource);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockerInterface::free()
     */
    public function free(UserInterface $user, LockableResourceInterface $resource, \Closure $action): void
    {
        if(null === $token = $this->pool->getToken($resource))
            throw new LockTokenExpiredException("Lock on resource '{$resource->getLockableName()}' cannot be revoked as not previous lock has been assigned to it");

        switch ($token->getType()) {
            case LockToken::EXCLUSIVE:
            case LockToken::SHARE:
                if(!$token->isMaster($user))
                    throw new LockTokenExpiredException("Your token has been revoked or is invalid as master resource or you're not setted as master on resource '{$resource->getLockableName()}'");
                break;
            case LockToken::FULL:
                if(!$token->isMaster($user) && !$token->isSharedWith($user))
                    throw new LockTokenExpiredException("Your token has been revoked or is invalid as master or you're not sharing this resource with the master '{$token->getMaster()}' on resource '{$resource->getLockableName()}'");
                break;
        }
        
        try {
            if(false === $this->pool->deleteToken($resource))
                throw new UnlockErrorException("Lock token cannot be revoked for resource '{$resource->getLockableName()}'. Lock token has been restored with sucess. Action has been canceled.");
            $action->call($resource);
        } catch (TokenPoolTransactionErrorException $e) {
            throw new UnlockErrorException(\sprintf("An error happen when revoking a lock on resource '%s'. Token pool failed to restore to its original state. See '%s' keys that might be inconsistent. Action has been canceled.",
                $resource->getLockableName(),
                \implode(", ", $e->getKeys())));
        }
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockerInterface::bypass()
     */
    public function bypass(UserInterface $user, LockableResourceInterface $resource, \DateInterval $duration): void
    {
        $this->acquireLock(self::generateToken($resource->getLockableName(), $user, $duration), $resource);
    }
    
    /**
     * Initialize a new lock token
     * 
     * @param string $resource
     *   Resource to assign the token
     * @param UserInterface $master
     *   User master
     * @param \DateInterval $duration
     *   Validation duration
     * @param array|null $users
     *   User which to share the token
     * @param int $type
     *   Token type...
     * 
     * @return LockToken
     *   Lock token initialized
     */
    private static function generateToken(
        string $resource, 
        UserInterface $master, 
        \DateInterval $duration, 
        ?array $users = null, 
        int $type = LockToken::EXCLUSIVE): LockToken
    {
        $token = new LockToken($resource, $type);
        $token->setMaster($master);
        $token->setValidity($duration);
        
        foreach ($users ?? [] as $user)
            $token->shareWith($user);
        
        return $token;
    }
    
    /**
     * Acquire a lock for the given token on the resource
     * 
     * @param LockToken $token
     *   Token to save
     * @param LockableResourceInterface $resource
     *   Resource which the lock is setted
     * 
     * @throws LockErrorException
     *   When the pool cannot save the lock pool
     */
    private function acquireLock(LockToken $token, LockableResourceInterface $resource): void
    {  
        try {
            if(false === $this->pool->saveToken($token, $resource))
                throw new LockErrorException("An error happen when assigning a lock on resource '{$resource->getLockableName()}'. Change has been reverted successfully");
        } catch (TokenPoolTransactionErrorException $e) {
            throw new LockErrorException(\sprintf("An error happen when assigning a lock on resource '%s'. Token pool failed to restore its original state. See '%s' keys that might be inconsistent",
                $resource->getLockableName(),
                \implode(", ", $e->getKeys())));
        }
    }
    
}
