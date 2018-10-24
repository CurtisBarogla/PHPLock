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
 
namespace Ness\Component\Lockey\Storage\Adapter;

/**
 * Makes the link between an external store dealing with a raw values representing LockToken
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenStoreAdapterInterface
{
    
    /**
     * Get a token representation from the store for the given resource
     * 
     * @param string $resource
     *   Resource name which to get the token
     * 
     * @return string|null
     *   A string representation of the lock token or null if no token found for the given resource
     */
    public function get(string $resource): ?string;
    
    /**
     * Add a token into the store
     * 
     * @param string $resource
     *   Resource name
     * @param string $token
     *   String token representation
     * @param int $duration
     *   Lock token validity duration
     * 
     * @return bool
     *   True if the token has been stored successfully. False otherwise
     */
    public function add(string $resource, string $token, int $duration): bool;
    
    /**
     * Remove a token from the store
     * 
     * @param string $resource
     *   Resource name
     * 
     * @return bool
     *   True if the token has been removed with success. False otherwise
     */
    public function remove(string $resource): bool;
    
}
