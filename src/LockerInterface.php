<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Lockery component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
 
namespace Ness\Component\Lockery;

use Ness\Component\Lockery\Exception\LockErrorException;
use Ness\Component\Lockery\Exception\UnlockErrorException;
use Ness\Component\Lockery\Exception\InvalidArgumentException;

/**
 * Manage lock of resources
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockerInterface
{
        
    /**
     * Lock a resource for a specific amount of time.
     * A locked resource cannot be accessed during the locking time. <br />
     * The given resource MUST be locked only for other users. <br />
     * The resource MUST still be accessible for the user who initiated the lock no matter what during the locking duration. <br />
     * If the given resource is currently locked, nothing will be performed <br />
     * Resource name MUST be compliant with pattern [A-Za-z0-9_.-] and length cannot be greater than 31 characters
     * 
     * @param LockableResourceInterface $resource
     *   Resource to lock
     * @param \DateInterval $duration
     *   Interval of time which the resource is locked
     *   
     * @throws LockErrorException
     *   When the resource cannot be locked
     * @throws InvalidArgumentException
     *   When resource name is invalid
     */
    public function lock(LockableResourceInterface $resource, \DateInterval $duration): void;
    
    /**
     * Remove a lock applied to the given resource.
     * If the given resource has been not previously locked, nothing will be performed. <br />
     * If for whatever reason, the lock is not assigned anymore to the user whose initiate the lock when the resource is freed, a UnlockErrorException is thrown
     * Resource name MUST be compliant with pattern [A-Za-z0-9_.-] and length cannot be greater than 31 characters
     * 
     * @param LockableResourceInterface $resource
     *   Resource to free
     *   
     * @throws UnlockErrorException
     *   When the resource cannot be unlocked
     * @throws InvalidArgumentException
     *   When resource name is invalid
     */
    public function free(LockableResourceInterface $resource): void;
    
    /**
     * Bypass an already locked resource by canceling the current lock and lock it for a certain amont of time
     * This method MUST be implemented as revoking the lock on the given resource for the user responsible of the lock. <br />
     * If the resource has been not previously locked, the resource will be simply locked. <br />
     * Resource name MUST be compliant with pattern [A-Za-z0-9_.-] and length cannot be greater than 31 characters
     * 
     * @param LockableResourceInterface $resource
     *   Resource to bypass
     * @param \DateInterval $duration
     *   Interval of time which the resource is locked
     * 
     * @throws UnlockErrorException
     *   When the resource cannot be unlocked
     * @throws LockErrorException
     *   When the resource cannot be locked
     * @throws InvalidArgumentException
     *   When resource name is invalid
     */
    public function bypass(LockableResourceInterface $resource, \DateInterval $duration): void;
    
    /**
     * Check if the given resource if locked.
     * Resource name MUST be compliant with pattern [A-Za-z0-9_.-] and length cannot be greater than 31 characters
     * 
     * @param LockableResourceInterface $resource
     *   Resource to check
     * 
     * @return \DateTimeImmutable|null
     *   Will return a \DateTimeImmutable corresponding to the date which the resource will be free. Returns null if the resource is free
     * @throws InvalidArgumentException
     *   When resource name is invalid
     */
    public function checkLocked(LockableResourceInterface $resource): ?\DateTimeImmutable;
    
}
