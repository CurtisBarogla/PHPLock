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
 * Level 2 Resource
 * 
 * Fixture Only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MozResource implements LockableResourceInterface
{
    
    /**
     * BarResource fixture
     * 
     * @var BarResource
     */
    private $bar;
    
    /**
     * Initialize BarResource
     * 
     * @param BarResource $bar
     *   BarResource
     */
    public function __construct(BarResource $bar)
    {
        $this->bar = $bar;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "MozResource";
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return [$this->bar];
    }

}
