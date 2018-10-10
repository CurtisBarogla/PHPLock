<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Lockery component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
 
namespace Ness\Component\Lockey;

/**
 * Make a component lockable.
 * This component can interact with a LockerInterface component to be locked or freed at will
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockableResourceInterface
{
    
    /**
     * Get an identifier representing the resource loackable.
     * This identifier MUST be unique among your application
     * Resource name MUST be compliant with pattern [A-Za-z0-9_.-] and length cannot be greater than 31 characters
     * 
     * @return string
     *   Resource identifier
     */
    public function getLockableResourceName(): string;
    
}
