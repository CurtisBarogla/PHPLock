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
 
namespace Ness\Component\Lockey\Generator;

use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\LockToken;

/**
 * Responsible to generate lock token
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenGeneratorInterface
{
    
    /**
     * Generate a token from the given resource
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to assign to lock token
     * 
     * @return LockToken
     *   A lock token initialized
     */
    public function generate(LockableResourceInterface $resource): LockToken;
    
}
