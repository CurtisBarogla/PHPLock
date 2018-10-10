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

use Psr\Cache\CacheItemPoolInterface;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

/**
 * Use a PSR-6 Cache pool to store lock resource token.
 * If a pool supporting tags is passed into the constructor, tokens will be tagged and therefore can be cleared massively via clear()
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolTokenResourceStorage implements TokenResourceStorageInterface
{
    
    /**
     * Cache pool
     * 
     * @var CacheItemPoolInterface
     */
    private $pool;
    
    /**
     * Identify key into the cache
     *
     * @var string
     */
    public const PREFIX = "ness_token_resource_PSR6_";
    
    /**
     * Tag used to mark item if taggable pool is provided
     *
     * @var string
     */
    public const TAG = "ness_token_resource_tag";
    
    /**
     * Initialize lock token storage
     * A TaggableCacheItemPool implementation can be provide
     * 
     * @param CacheItemPoolInterface $pool
     *   PSR-6 Cache pool implementation
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::get()
     */
    public function get(string $resource): ?string
    {
        return ( false !== ($item = $this->pool->getItem(self::PREFIX.$resource))->isHit() ) ? $item->get() : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::add()
     */
    public function add(string $resource, string $token, int $validitity): bool
    {
        $item = $this->pool->getItem(self::PREFIX.$resource)->set($token)->expiresAfter($validitity);
        if($item instanceof TaggableCacheItemInterface)
            $item->setTags([self::TAG]);
        
        return $this->pool->save($item);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::remove()
     */
    public function remove(string $resource): bool
    {
        return $this->pool->deleteItem(self::PREFIX.$resource);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface::clear()
     */
    public function clear(): void
    {
        if(!$this->pool instanceof TaggableCacheItemPoolInterface)
            return;
        
        $this->pool->invalidateTag(self::TAG);
    }
    
}
