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
 
namespace Ness\Component\Lockery\Generator;

use Ness\Component\Lockery\LockToken;
use Ness\Component\Lockery\LockableResourceInterface;

/**
 * Simply use random_bytes native php function to generate a random value
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RandomBytesLockTokenGenerator implements LockTokenGeneratorInterface
{
    
    /**
     * Token length
     * 
     * @var int
     */
    private $length;
    
    /**
     * Initialize generator
     * 
     * @param int $length
     *   Token length
     */
    public function __construct(int $length = 32)
    {
        $this->length = $length >> 1;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Generator\LockTokenGeneratorInterface::generate()
     */
    public function generate(LockableResourceInterface $resource): LockToken
    {
        return new LockToken($resource->getLockableResourceName(), \bin2hex(\random_bytes($this->length)));
    }
    
}
