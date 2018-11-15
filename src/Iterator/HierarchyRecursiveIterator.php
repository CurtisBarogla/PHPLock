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
 
namespace Ness\Component\Lockey\Iterator;

use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\Exception\InvalidArgumentException;

/**
 * Iterator for recursively iterate over a resource declaring complexe hierarchy
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class HierarchyRecursiveIterator implements \RecursiveIterator
{
    
    /**
     * Base resource
     * 
     * @var LockableResourceInterface
     */
    private $resource;
    
    /**
     * Current index for the current resource
     * 
     * @var int
     */
    private $current;
    
    /**
     * Initialize iterator
     * 
     * @param LockableResourceInterface $resource
     *   Base resource
     */
    public function __construct(LockableResourceInterface $resource)
    {
        $this->resource = $resource;
        $this->current = 0;
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::hasChildren()
     */
    public function hasChildren()
    {
        return null !== $this->resource->getLockableHierarchy();
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::getChildren()
     */
    public function getChildren()
    {
        return new self($this->resource->getLockableHierarchy()[$this->current]);
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::current()
     * 
     * @throws InvalidArgumentException
     *   When not an implementation of LockableResourceInterface
     */
    public function current()
    {
        if(! ($current = $this->resource->getLockableHierarchy()[$this->current]) instanceof LockableResourceInterface)
            throw new InvalidArgumentException("Resource declared into hierarchy of resource {$this->resource->getLockableName()} MUST be an instance of LockableResourceInterface");
        
        return $this->resource->getLockableHierarchy()[$this->current];
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::next()
     */
    public function next()
    {
        $this->current++;
    }

    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::key()
     */
    public function key()
    {
        return $this->resource->getLockableHierarchy()[$this->current]->getLockableName();
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::valid()
     */
    public function valid()
    {
        return isset($this->resource->getLockableHierarchy()[$this->current]);
    }
    
    /**
     * {@inheritDoc}
     * @see \RecursiveIterator::rewind()
     */
    public function rewind()
    {
        $this->current = 0;
    }

}
