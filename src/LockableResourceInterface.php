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
 
namespace Ness\Component\Lockey;

/**
 * Make a component lockable
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockableResourceInterface
{
    
    /**
     * Get an unique identifier representing the resource name 
     * 
     * @return string
     *   Resource name
     */
    public function getLockableName(): string;
    
    /**
     * Get the lockable hierarchy assigned to this resource.
     * All resources declared here will be locked/freed when action on the main one occured
     * 
     * @return LockableResourceInterface[]|null
     *   A list of side resources to lock/free or null if no hierarchy at all
     */
    public function getLockableHierarchy(): ?array;
    
}
