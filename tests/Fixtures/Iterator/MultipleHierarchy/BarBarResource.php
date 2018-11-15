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
 
namespace NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy;

use Ness\Component\Lockey\LockableResourceInterface;

/**
 * Level 1 Resource
 * 
 * Fixture Only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class BarBarResource implements LockableResourceInterface
{
    
    /**
     * BarResource
     * 
     * @var BarResource
     */
    private $bar;
    
    /**
     * FooBarResource
     * 
     * @var FooBarResource
     */
    private $fooBar;
    
    /**
     * Initialize BarBarResource
     * 
     * @param BarResource $bar
     *   BarResource
     * @param FooBarResource $fooBar
     *   FooBarResource
     */
    public function __construct(BarResource $bar, FooBarResource $fooBar)
    {
        $this->bar = $bar;
        $this->fooBar = $fooBar;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "BarBarResource";
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return [
            $this->bar,
            $this->fooBar
        ];
    }

}
