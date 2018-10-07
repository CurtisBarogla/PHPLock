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

use Psr\SimpleCache\CacheInterface;

/**
 * Use a PSR-16 Cache implementation to store lock resource tokens
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTokenResourceStorage implements TokenResourceStorageInterface
{
    
    /**
     * PSR-16 Cache
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Identify key into the cache
     * 
     * @var string
     */
    public const PREFIX = "ness_token_resource_";
    
    /**
     * Initialize storage
     * 
     * @param CacheInterface $cache
     *   Psr-16 Cache implementation
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Resource\TokenResourceStorageInterface::get()
     */
    public function get(string $resource): ?string
    {
        return $this->cache->get(self::PREFIX.$resource, null);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Resource\TokenResourceStorageInterface::add()
     */
    public function add(string $resource, string $token, int $validitity): bool
    {
        return $this->cache->set(self::PREFIX.$resource, $token, $validitity);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Resource\TokenResourceStorageInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return $this->cache->delete(self::PREFIX.$resource);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\Resource\TokenResourceStorageInterface::clear()
     */
    public function clear(): void
    {
        // cannot remove specific keys with PSR-16 :(
        return;
    }
    
}
