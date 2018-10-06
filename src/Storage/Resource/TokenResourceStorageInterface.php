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
 
namespace Ness\Component\Lockery\Storage\Resource;

/**
 * Responsible to persist resource tokens
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface TokenResourceStorageInterface
{
    
    /**
     * Get a resource token representation for a given resource.
     * Returns null if no token found for the given resource
     * 
     * @param string $resource
     *   Resource name
     *   
     * @return string|null
     *   A resource token representation or null if no token found for the given resource
     */
    public function get(string $resource): ?string;
    
    /**
     * Add a representation of a resource token into the store
     *
     * @param string $resource
     *   Resource name
     * @param string $token
     *   Representation of the resource token
     * @param int $validitity
     *   Representation in seconds of the token's validity
     *
     * @return bool
     *   True if the token has been stored with success. False otherwise
     */
    public function add(string $resource, string $token, int $validitity): bool;
    
    /**
     * Remove a resource token from the store
     *
     * @param string $resource
     *   Resource name
     *
     * @return bool
     *   True if the token has been removed from the store with success. False otherwise
     */
    public function remove(string $resource): bool;
    
    /**
     * Attempt to clear the resource store from all presents resource tokens
     */
    public function clear(): void;
    
}
