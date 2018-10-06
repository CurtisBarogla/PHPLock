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
 * Normalize a lock token under a json format and denormalize it with the factory furnished by the LockToken class
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonLockTokenFormatter implements LockTokenFormatterInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Format\LockTokenFormatterInterface::normalize()
     */
    public function normalize(LockToken $token): string
    {
        // no error should happen
        return \json_encode($token);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Format\LockTokenFormatterInterface::denormalize()
     */
    public function denormalize(string $token): LockToken
    {
        try {
            return LockToken::createLockTokenFromJson($token);
        } catch (\RuntimeException $e) {
            throw new FormatterException();
        }
    }
    
}
