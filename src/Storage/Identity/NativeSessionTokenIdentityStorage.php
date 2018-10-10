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
 
namespace Ness\Component\Lockey\Storage\Identity;

/**
 * Simply use the native session mechanism of php to store identity token
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeSessionTokenIdentityStorage implements TokenIdentityStorageInterface
{
    
    /**
     * $_SESSION
     * 
     * @var array
     */
    private $session;
    
    /**
     * Identity the where the identity tokens are stored
     * 
     * @var string
     */
    private const SESSION_IDENTIFIER = "ness_native_session_token_identity_";
    
    /**
     * Initialize token identity storage
     * 
     * @throws \LogicException
     *   When session is not enabled
     */
    public function __construct()
    {
        if(session_status() !== PHP_SESSION_ACTIVE)
            throw new \LogicException("Session MUST be enable to use the NativeSessionTokenIdentityStorage");
        
        $this->session = &$_SESSION;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Identity\TokenIdentityStorageInterface::get()
     */
    public function get(string $resource): ?string
    {
        return $this->session[self::SESSION_IDENTIFIER][$resource] ?? null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Identity\TokenIdentityStorageInterface::add()
     */
    public function add(string $resource, string $token): bool
    {
        $this->session[self::SESSION_IDENTIFIER][$resource] = $token;
        
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Identity\TokenIdentityStorageInterface::remove()
     */
    public function remove(string $resource): bool
    {
        if(!isset($this->session[self::SESSION_IDENTIFIER][$resource]))
            return false;
        
        unset($this->session[self::SESSION_IDENTIFIER][$resource]);
        
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Identity\TokenIdentityStorageInterface::clear()
     */
    public function clear(): void
    {
        unset($this->session[self::SESSION_IDENTIFIER]);
    }

}
