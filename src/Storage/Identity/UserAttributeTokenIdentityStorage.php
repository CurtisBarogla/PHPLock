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

use Ness\Component\User\UserAwareInterface;
use Ness\Component\User\Traits\UserAwareTrait;

/**
 * Store the token into the user via an attribute
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UserAttributeTokenIdentityStorage implements TokenIdentityStorageInterface, UserAwareInterface
{
    
    use UserAwareTrait;
    
    /**
     * Used to identify the attribute storing all identity tokens
     * 
     * @var string
     */
    public const ATTRIBUTE_IDENTIFIER = "ness_user_attribute_identity_token_";
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Identity\TokenIdentityStorageInterface::add()
     */
    public function add(string $resource, string $token): bool
    {
        $this->updateAttribute(function(array& $locked) use ($resource, $token): void {
            $locked[$resource] = $token;
        });
        
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Identity\TokenIdentityStorageInterface::get()
     */
    public function get(string $resource): ?string
    {
        return $this->getUser()->getAttribute(self::ATTRIBUTE_IDENTIFIER)[$resource] ?? null;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Identity\TokenIdentityStorageInterface::remove()
     */
    public function remove(string $resource): bool
    {
        $success = true;
        
        $this->updateAttribute(function(array& $locked) use ($resource, &$success): void {
            if(!isset($locked[$resource])) {
                $success = false;
                return;
            }
            
            unset($locked[$resource]); 
        });
        
        return $success;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Identity\TokenIdentityStorageInterface::clear()
     */
    public function clear(): void
    {
        $this->getUser()->deleteAttribute(self::ATTRIBUTE_IDENTIFIER);
    }
    
    /**
     * Update the current user attribute array holding all identity tokens registered
     * 
     * @param \Closure $update
     *   Action to perform on the current array representing all locked resource.
     *   Takes as single parameter all currently locked resources passed by reference
     */
    private function updateAttribute(\Closure $update): void
    {
        $locked = $this->getUser()->getAttribute(self::ATTRIBUTE_IDENTIFIER) ?? [];
        \call_user_func_array($update, [&$locked]);
        $this->getUser()->addAttribute(self::ATTRIBUTE_IDENTIFIER, $locked);
    }

}
