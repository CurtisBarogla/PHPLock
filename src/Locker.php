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
 
namespace Ness\Component\Lockery;

use Ness\Component\Lockery\Format\LockTokenFormatterInterface;
use Ness\Component\Lockery\Storage\LockTokenPoolInterface;
use Ness\Component\Lockery\Format\LockTokenFormatterAwareInterface;
use Ness\Component\Lockery\Generator\LockTokenGeneratorInterface;
use Ness\Component\Lockery\Exception\UnlockErrorException;
use Ness\Component\Lockery\Exception\LockErrorException;
use Ness\Component\Lockery\Exception\InvalidArgumentException;

/**
 * Basic implementation of LockerInterface
 * Based on a LockTokenPool, a generator and a token formatter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Locker implements LockerInterface
{
    
    /**
     * Token formatter
     * 
     * @var LockTokenFormatterInterface
     */
    private $formatter;
    
    /**
     * Token pool
     * 
     * @var LockTokenPoolInterface
     */
    private $tokenPool;
    
    /**
     * Token generator
     * 
     * @var LockTokenGeneratorInterface
     */
    private $generator;
    
    /**
     * Index which represents identity token provided by the pool
     * 
     * @var int
     */
    private const IDENTITY_TOKEN = 0;
    
    /**
     * Index which represents resource token provided by the pool
     *
     * @var int
     */
    private const RESOURCE_TOKEN = 1;
    
    /**
     * Initialize locker
     * 
     * @param LockTokenFormatterInterface $formatter
     *   Token formatter
     * @param LockTokenPoolInterface $tokenPool
     *   Token pool
     * @param LockTokenGeneratorInterface $generator
     *   Token genenerator
     */
    public function __construct(
        LockTokenFormatterInterface $formatter, 
        LockTokenPoolInterface $tokenPool, 
        LockTokenGeneratorInterface $generator)
    {
        $this->formatter = $formatter;
        $this->tokenPool = $tokenPool;
        $this->generator = $generator;
        
        if($this->tokenPool instanceof LockTokenFormatterAwareInterface)
            $this->tokenPool->setFormatter($this->formatter);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\LockerInterface::lock()
     */
    public function lock(LockableResourceInterface $resource, \DateInterval $duration): void
    {
        $this->validateResource($resource->getLockableResourceName());
        
        if(null !== $this->tokenPool->getToken($resource))
            return;
        
        $token = $this->generator->generate($resource);
        $token->setExpiration($duration);
        
        if(false === $this->tokenPool->addToken($token))
            throw new LockErrorException("An error happen when trying to lock this resource : '{$resource->getLockableResourceName()}'");
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\LockerInterface::free()
     */
    public function free(LockableResourceInterface $resource): void
    {
        $this->delete(true, $resource);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\LockerInterface::bypass()
     */
    public function bypass(LockableResourceInterface $resource, \DateInterval $duration): void
    {
        $this->delete(false, $resource);
        $this->lock($resource, $duration);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\LockerInterface::checkLocked()
     */
    public function checkLocked(LockableResourceInterface $resource): ?\DateTimeImmutable
    {
        $this->validateResource($resource->getLockableResourceName());
        
        if(null === $tokens = $this->tokenPool->getToken($resource))
            return null;
        
        return ($tokens[self::IDENTITY_TOKEN] == $tokens[self::RESOURCE_TOKEN]) 
            ? null 
            : new \DateTimeImmutable("@{$tokens[self::RESOURCE_TOKEN]->getExpiration()}");
    }
    
    /**
     * Delete a lock token by its resource
     * 
     * @param bool $tokenComparaison
     *   If comparaison must be performed on lock tokens provided by the pool
     * @param LockableResourceInterface $resource
     *   Resource which the token must be removed
     * 
     * @throws UnlockErrorException
     *   When a error when trying to remove the lock token or when the given tokens are not same
     */
    private function delete(bool $tokenComparaison, LockableResourceInterface $resource): void
    {
        $this->validateResource($resource->getLockableResourceName());
        
        if(null === $tokens = $this->tokenPool->getToken($resource))
            return;
            
        if($tokenComparaison)
            if($tokens[self::IDENTITY_TOKEN] != $tokens[self::RESOURCE_TOKEN])
                throw new UnlockErrorException("Lock Token assigned has been expired for resource '{$resource->getLockableResourceName()}'");
                
        if(false === $this->tokenPool->removeToken($resource))
            throw new UnlockErrorException("An error happen when trying to unlock this resource : '{$resource->getLockableResourceName()}'");
    }
    
    /**
     * Validate a resource name
     * 
     * @param string $resource
     *   Resource name
     * 
     * @throws InvalidArgumentException
     *   When resource name is considered invalid
     */
    private function validateResource(string $resource): void
    {
        if(0 === \preg_match("#^[A-Za-z0-9_.-]+$#", $resource) || \strlen($resource) > 31)
            throw new InvalidArgumentException("This resource name '{$resource}' is invalid !");
    }

}
