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
 
namespace Ness\Component\Lockey\Format;

use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\Exception\FormatterException;

/**
 * Simply use serialize and unserialize functions of php as LockToken implements Serializable
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeLockTokenFormatter implements LockTokenFormatterInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Format\LockTokenFormatterInterface::normalize()
     */
    public function normalize(LockToken $token): string
    {
        // no error should happen
        return \serialize($token);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Format\LockTokenFormatterInterface::denormalize()
     */
    public function denormalize(string $token): LockToken
    {
        if($token[0] !== 'C' || $token[1] !== ':')
            throw new FormatterException();
        
        if(false !== $token = @\unserialize($token))
            return $token;
        
        throw new FormatterException();
    }
    
}
