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
 
namespace Ness\Component\Lockey\Storage;

use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\Exception\TokenPoolTransactionErrorException;
use Ness\Component\Lockey\Exception\InvalidArgumentException;

/**
 * Responsible to load and save lock token on a resource respecting its hierarchy
 * Resource name MUST comply rules :
 * <ul> 
 *    <li>- Length  : > 3 <= 42</li>
 *    <li>- Pattern : [A-Za-z0-9]</li>
 * </ul>
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenPoolInterface
{
    
    /**
     * Try to get a lock token attribute to the given resource.
     * This token can be either a token attribute to the main resource or a hierarchical one declared into the resource
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to get the token
     *   
     * @return LockToken|null
     *   A lock token attribute to the given resource or attribute to a hierarchical one or null if not found
     *   
     * @throws InvalidArgumentException
     *   When given resource name does not comply required pattern
     * @throws InvalidArgumentException
     *   When a hierarchy has been declared and a member is not a LockableResourceInterface compliant component
     */
    public function getToken(LockableResourceInterface $resource): ?LockToken;
    
    /**
     * Save a token for the given resource.
     * Depending of the configuration of the resource, this operation will attribute a lock token on the main resource and its hierarchy if setted
     * This operation MUST be atomic, therefore in a situation when a resource has a declared hierarchy and a token failed to be stored, nothing must be performed
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to set a lock token
     * 
     * @return bool
     *   True if the lock has been setted for the given resource. False otherwise
     *   
     * @throws TokenPoolTransactionErrorException
     *   When an error happen when restoring the pool in case of error
     * @throws InvalidArgumentException
     *   When given resource name does not comply required pattern
     * @throws InvalidArgumentException
     *   When a hierarchy has been declared and a member is not a LockableResourceInterface compliant component
     */
    public function saveToken(LockToken $token, LockableResourceInterface $resource): bool;
    
    /**
     * Delete a lock token attribute to the given resource
     * Depending of the configuration of the resource, this operation will remove a lock token on the main resource and its hierarchy if setted
     * This operation MUST be atomic, therefore in a situation when a resource has a declared hierarchy and a token failed to be removed, nothing must be performed
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to remove the token
     * 
     * @return bool
     *   True if the lock token has been removed for the given resource. False otherwise
     *   
     * @throws TokenPoolTransactionErrorException
     *   When an error happen when restoring the pool in case of error
     * @throws InvalidArgumentException
     *   When given resource name does not comply required pattern
     * @throws InvalidArgumentException
     *   When a hierarchy has been declared and a member is not a LockableResourceInterface compliant component
     */
    public function deleteToken(LockableResourceInterface $resource): bool;
    
}
