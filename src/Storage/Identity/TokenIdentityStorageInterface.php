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
 
namespace Ness\Component\Lockery\Storage\Identity;

/**
 * Responsible to store an identity token to identify a user when a resource is locked
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface TokenIdentityStorageInterface
{
    
    /**
     * Get an identity token representation for a given resource.
     * Returns null if no token found for the given resource
     * 
     * @param string $resource
     *   Resource name
     *   
     * @return string|null
     *   An identity token representation or null if no token found for the given resource
     */
    public function get(string $resource): ?string;
    
    /**
     * Add a representation of an identity token into the store
     * 
     * @param string $resource
     *   Resource name
     * @param string $token
     *   Representation of the identity token
     * 
     * @return bool
     *   True if the token has been stored with success. False otherwise
     */
    public function add(string $resource, string $token): bool;
    
    /**
     * Remove an identity token from the store
     * 
     * @param string $resource
     *   Resource name
     * 
     * @return bool
     *   True if the token has been removed from the store with success. False otherwise
     */
    public function remove(string $resource): bool;
    
    /**
     * Attempt to clear the identity store from all presents identity tokens 
     */
    public function clear(): void;
    
}
