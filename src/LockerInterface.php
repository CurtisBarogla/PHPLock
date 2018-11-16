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
use Ness\Component\Lockey\Exception\LockErrorException;
use Ness\Component\Lockey\Exception\UnlockErrorException;
use Ness\Component\Lockey\Exception\LockTokenExpiredException;

/**
 * Responsible to interact with LockableResource attributing lock tokens and free them if needed
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockerInterface
{
 
    /**
     * Get the current state of a lock on the given resource for the given user
     * 
     * @param UserInterface
     *   User which to check the state about the given resource
     * @param LockableResourceInterface $resource
     *   Resource to check
     * 
     * @return LockState
     *   Information about the current lock on the resource
     */
    public function getState(UserInterface $user, LockableResourceInterface $resource): LockState;
    
    /**
     * Attribute an exclusive lock on the given resource to the given user for the given duration.
     * This duration represents the time which the user is able to free the resource and perform action on it.
     * If a hierarchy is declared, all related resources MUST be locked and the operation MUST be transactional.
     * If the resource is currently locked, nothing must be performed
     * 
     * @param UserInterface $user
     *   User which the lock is attributed
     * @param LockableResourceInterface $resource
     *   Lock duration
     * @param \DateInterval $duration
     *   Duration of the lock
     *   
     * @throws LockErrorException
     *   When the lock cannot be acquired on the given resource
     */
    public function exclusive(UserInterface $user, LockableResourceInterface $resource, \DateInterval $duration): void;
    
    /**
     * Attribute an share lock on the given resource to the given user and share users for the given duration.
     * This duration represents the time which the users are able to free the resource and perform actions on it
     * If a hierarchy is declared, all related resources MUST be locked and the operation MUST be transactional.
     * If the resource is currently locked, nothing must be performed
     * 
     * @param UserInterface $user
     *   User which the lock is attributed
     * @param UserInterface[] $users
     *   List of users which the token is shared
     * @param LockableResourceInterface $resource
     *   Resource to lock
     * @param \DateInterval $duration
     *   Lock duration
     * @param bool $full
     *   If setted to false, will share the resource only in read mode, therefore, only the master is able to perform action on the resource
     *   If setted to true, all users are able to perform actions on the resource
     *   
     * @throws LockErrorException
     *   When the lock cannot be acquired on the given resource
     */
    public function share(UserInterface $user, array $users, LockableResourceInterface $resource, \DateInterval $duration, bool $full = false): void;
    
    /**
     * Perform the given action on the given resource and free it.
     * Depending of the lock token found for the given resource and the given user, action must be performed or deny.
     * If a hierarchy is declared, all related resources MUST be freed and the operation MUST be transactional.
     * If no lock token has been assigned to the resource, action MUST be denied no matter what
     * 
     * @param UserInterface $user
     *   User which perform the action
     * @param LockableResourceInterface $resource
     *   Resource to free
     * @param \Closure $action
     *   Action to perform on the resource. $this is assigned to the resource
     *   If an error happen during the action, lock assigned must not be revoked
     *   
     * @throws UnlockErrorException
     *   When lock cannot be removed from the given resource
     * @throws LockTokenExpiredException
     *   When the given token does not correspond to the given user depending of the situation
     * @throws LockTokenExpiredException
     *   When no token has been previously assigned to the given resource
     */
    public function free(UserInterface $user, LockableResourceInterface $resource, \Closure $action): void;
    
    /**
     * Bypass a lock attributed to the given resource.
     * If the resource is not currently locked, a simple exclusive lock will be attributed to the given resource for the given user for the given duration
     * If a hierarchy is declared, all related resources MUST be locked and the operation MUST be transactional.
     * If the resource is actually locked, it will cancel it.
     * 
     * @param UserInterface $user
     *   User which the lock is attributed
     * @param LockableResourceInterface $resource
     *   Resource to bypass
     * @param \DateInterval $duration
     *   Lock duration
     *   
     * @throws LockErrorException
     *   When the lock cannot be acquired on the given resource
     */
    public function bypass(UserInterface $user, LockableResourceInterface $resource, \DateInterval $duration): void;
    
}
