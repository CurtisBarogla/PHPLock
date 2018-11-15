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
 * Final resource level...
 * 
 * Fixture Only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class KekResource implements LockableResourceInterface
{

    /**
     * MozResource
     * 
     * @var MozResource
     */
    private $moz;
    
    /**
     * MozFooResource
     * 
     * @var MozFooResource
     */
    private $mozFoo;
    
    /**
     * Initialize KekResource
     * 
     * @param MozResource $moz
     *   MozResource
     * @param MozFooResource $mozFoo
     *   MozFooResource
     */
    public function __construct(MozResource $moz, MozFooResource $mozFoo)
    {
        $this->moz = $moz;
        $this->mozFoo = $mozFoo;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
     */
    public function getLockableName(): string
    {
        return "KekResource";
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
     */
    public function getLockableHierarchy(): ?array
    {
        return [
            $this->moz,
            $this->mozFoo
        ];
    }

}
