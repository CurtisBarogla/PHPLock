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
class BarResource implements LockableResourceInterface
{
    
    /**
     * FooResource
     * 
     * @var FooResource
     */
    private $foo;
    
    /**
     * FooFooResource
     * 
     * @var FooFooResource
     */
    private $fooFoo;
    
    /**
     * Initialize BarResource
     * 
     * @param FooResource $foo
     *   FooResource
     * @param FooFooResource $fooFoo
     *   FooFooResource
     */
    public function __construct(FooResource $foo, FooFooResource $fooFoo)
    {
        $this->foo = $foo;
        $this->fooFoo = $fooFoo;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "BarResource";
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return [
            $this->foo,
            $this->fooFoo
        ];
    }
    
}
