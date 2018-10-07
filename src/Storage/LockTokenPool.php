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
 
namespace Ness\Component\Lockery\Storage;

use Ness\Component\Lockery\LockToken;
use Ness\Component\Lockery\LockableResourceInterface;
use Ness\Component\Lockery\Format\LockTokenFormatterAwareInterface;
use Ness\Component\Lockery\Format\LockTokenFormatterInterface;
use Ness\Component\Lockery\Storage\Identity\TokenIdentityStorageInterface;
use Ness\Component\Lockery\Storage\Resource\TokenResourceStorageInterface;

/**
 * Native implementation of LockTokenPoolInterface
 * Based on a TokenFormatter, a IdentityTokenStorage and a ResourceTokenStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenPool implements LockTokenPoolInterface, LockTokenFormatterAwareInterface
{
    
    /**
     * Lock token formatter
     * 
     * @var LockTokenFormatterInterface
     */
    private $formatter;
    
    /**
     * Identity token storage
     * 
     * @var TokenIdentityStorageInterface 
     */
    private $identityStore;
    
    /**
     * Resource token storage
     * 
     * @var TokenResourceStorageInterface
     */
    private $resourceStore;
    
    /**
     * Initialize lock token pool
     * 
     * @param TokenIdentityStorageInterface $identityStore
     *   Token identity store
     * @param TokenResourceStorageInterface $resourceStore
     *   Token resource store
     */
    public function __construct(TokenIdentityStorageInterface $identityStore, TokenResourceStorageInterface $resourceStore)
    {
        $this->identityStore = $identityStore;
        $this->resourceStore = $resourceStore;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\LockTokenPoolInterface::addToken()
     */
    public function addToken(LockToken $token): bool
    {
        $resource = $token->getResource();
        $expiration = $token->getExpiration() - \time();
        $token = $this->formatter->normalize($token);
        
        if(false === $this->identityStore->add($resource, $token))
            return false;
        
        if(false === $this->resourceStore->add($resource, $token, $expiration)) {
            $this->identityStore->remove($resource);
            
            return false;
        }
        
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\LockTokenPoolInterface::getToken()
     */
    public function getToken(LockableResourceInterface $resource): ?array
    {
        $resource = $resource->getLockableResourceName();
        
        if(null === $resourceToken = $this->resourceStore->get($resource)) {
            // get rid of the outdated token from the identity store
            if(null !== $this->identityStore->get($resource))
                $this->identityStore->remove($resource);
            
            return null;
        }
        
        return [
            (null !== $identityToken = $this->identityStore->get($resource)) 
                ? $this->formatter->denormalize($identityToken) 
                : null,
            $this->formatter->denormalize($resourceToken)
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\LockTokenPoolInterface::removeToken()
     */
    public function removeToken(LockableResourceInterface $resource): bool
    {
        $this->identityStore->remove($resource->getLockableResourceName());
        
        return $this->resourceStore->remove($resource->getLockableResourceName());
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Storage\LockTokenPoolInterface::clear()
     */
    public function clear(): void
    {
        $this->identityStore->clear();
        $this->resourceStore->clear();
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Format\LockTokenFormatterAwareInterface::setFormatter()
     */
    public function setFormatter(LockTokenFormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockery\Format\LockTokenFormatterAwareInterface::getFormatter()
     */
    public function getFormatter(): LockTokenFormatterInterface
    {
        return $this->formatter;
    }
    
}
