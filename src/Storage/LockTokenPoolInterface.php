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
 
namespace Ness\Component\Lockey\Storage;

use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\LockableResourceInterface;

/**
 * Provide a way to interact with lock token
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenPoolInterface
{
    
    /**
     * Register a lock token into the lock pool.
     * 
     * @param LockToken $token
     *   Lock token
     * 
     * @return bool
     *   True if the token has been persisted with success. False otherwise
     */
    public function addToken(LockToken $token): bool;
    
    /**
     * Get a set of tokens from the pool for the given resource.
     * First token MUST represents a token assigned to a specific user for the resource. This token can be null if not found <br />
     * Second token MUST represents a token assigned to a resource shared over all users <br />
     * Returns null if no token is representing the second one
     * 
     * @param LockableResourceInterface $resource
     *   Resource to check
     * 
     * @return LockToken[]|null
     *   A set of two tokens assigned to the given resource
     */
    public function getToken(LockableResourceInterface $resource): ?array;
    
    /**
     * Remove a lock token assigned to a resource
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to remove the token
     * 
     * @return bool
     *   True if the token has been removed with success. False otherwise
     */
    public function removeToken(LockableResourceInterface $resource): bool;
    
    /**
     * Clear the pool from all lock tokens
     */
    public function clear(): void;
    
}
