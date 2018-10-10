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
 
namespace Ness\Component\Lockey\Storage\Resource;

/**
 * Use apcu as a token resource store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuTokenResourceStorage implements TokenResourceStorageInterface
{
    
    /**
     * Identify keys stored via this storage
     * 
     * @var string
     */
    public const KEY_PREFIX = "ness_apcu_resource_token_";
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::get()
     */
    public function get(string $resource): ?string
    {
        return (false !== $token = \apcu_fetch(self::KEY_PREFIX.$resource)) ? $token : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::add()
     */
    public function add(string $resource, string $token, int $validitity): bool
    {
        return \apc_store(self::KEY_PREFIX.$resource, $token, $validitity);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return \apcu_delete(self::KEY_PREFIX.$resource);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::clear()
     */
    public function clear(): void
    {
        foreach (new \APCuIterator("#" . self::KEY_PREFIX . "#", APC_ITER_KEY) as $key => $value)   
            \apcu_delete($key);
    }

}
