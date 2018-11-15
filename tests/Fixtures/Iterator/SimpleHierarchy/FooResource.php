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
 * Main resource
 * 
 * Fixture Only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FooResource implements LockableResourceInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "FooResource";
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
