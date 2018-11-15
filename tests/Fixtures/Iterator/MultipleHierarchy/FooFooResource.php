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
 * Main FooFooResource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FooFooResource implements LockableResourceInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "FooFooResource";
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return null;
    }
    
}
