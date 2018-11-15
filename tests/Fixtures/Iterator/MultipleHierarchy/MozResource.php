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
     * BarBarResource
     * 
     * @var BarBarResource
     */
    private $barBar;
    
    /**
     * Initialize MozResource
     * 
     * @param BarBarResource $barBar
     *   BarBarResource
     */
    public function __construct(BarBarResource $barBar)
    {
        $this->barBar = $barBar;
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
        return [
            $this->barBar
        ];
    }

}
