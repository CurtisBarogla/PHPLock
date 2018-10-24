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

use Psr\Cache\CacheItemPoolInterface;

/**
 * Use PSR-6 Cache implementation to store lock token
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolLockTokenStoreAdapter implements LockTokenStoreAdapterInterface
{
    
    /**
     * Cache item pool
     * 
     * @var CacheItemPoolInterface
     */
    private $pool;
    
    /**
     * Initialize adapter
     * 
     * @param CacheItemPoolInterface $pool
     *   PSR-6 Cache item pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::get()
     */
    public function get(string $resource): ?string
    {
        return ( ($item = $this->pool->getItem($resource))->isHit()) ? $item->get() : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::add()
     */
    public function add(string $resource, string $token, int $duration): bool
    {
        return $this->pool->save($this->pool->getItem($resource)->set($token)->expiresAfter($duration));
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return $this->pool->deleteItem($resource);
    }
    
}
