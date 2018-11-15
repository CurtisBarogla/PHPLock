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
 
namespace NessTest\Component\Lockey\Fixtures\Iterator\SimpleHierarchy;

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
     * FooResource fixture
     * 
     * @var FooResource
     */
    private $foo;
    
    /**
     * Initialize BarResource
     * 
     * @param FooResource $foo
     *   FooResource
     */
    public function __construct(FooResource $foo)
    {
        $this->foo = $foo;
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
        return [$this->foo];
    }

}
