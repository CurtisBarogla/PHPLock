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
 * Use apcu store to store lock token
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuLockTokenStoreAdapter implements LockTokenStoreAdapterInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::get()
     */
    public function get(string $resource): ?string
    {
        return (false !== $token = \apcu_fetch($resource)) ? $token : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::add()
     */
    public function add(string $resource, string $token, int $duration): bool
    {
        return apcu_store($resource, $token, $duration);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return \apcu_delete($resource);
    }
    
}
