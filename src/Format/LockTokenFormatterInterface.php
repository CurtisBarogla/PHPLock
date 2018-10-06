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
 
namespace Ness\Component\Lockery\Format;

use Ness\Component\Lockery\LockToken;
use Ness\Component\Lockery\Exception\FormatterException;

/**
 * Responsible to normalize and denormalize lock tokens
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockTokenFormatterInterface
{
    
    /**
     * Normalize a lock token to be able to be stocked into a store
     * 
     * @param LockToken $token
     *   Lock token to normalize
     * 
     * @return string
     *   String representation of the lock token
     * 
     * @throws FormatterException
     *   When the given token is not handled by this formatter
     */
    public function normalize(LockToken $token): string;
    
    /**
     * Denormalize a lock token from its string representation
     * 
     * @param string $token
     *   Normalized lock token
     * 
     * @return LockToken
     *   Lock token restored from its normalized version
     * 
     * @throws FormatterException
     *   When the given string representation cannot be restored
     */
    public function denormalize(string $token): LockToken;
    
}
