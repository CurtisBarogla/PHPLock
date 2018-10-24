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

use Psr\SimpleCache\CacheInterface;

/**
 * Use a PSR-16 Cache implementation to store lock tokens
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheLockTokenStoreAdapter implements LockTokenStoreAdapterInterface
{
    
    /**
     * PSR-16 Cache implementation
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Initialize adapter
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::get()
     */
    public function get(string $resource): ?string
    {
        return $this->cache->get($resource, null);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::add()
     */
    public function add(string $resource, string $token, int $duration): bool
    {
        return $this->cache->set($resource, $token, $duration);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return $this->cache->delete($resource);
    }

}
