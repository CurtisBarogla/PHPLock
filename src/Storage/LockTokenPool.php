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
 
namespace Ness\Component\Lockey\Storage;

use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface;
use Ness\Component\Lockey\Exception\TokenPoolTransactionErrorException;
use Ness\Component\Lockey\Normalizer\ResourceNormalizerInterface;
use Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator;

/**
 * Native implementation of LockTokenInterface
 * Based on an adapter which establish the connection between the pool and a store
 * No verification are done whatsoever on the resource name as normalizer is handling this responsibility
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenPool implements LockTokenPoolInterface
{
    
    /**
     * Store adapter
     * 
     * @var LockTokenStoreAdapterInterface
     */
    private $adapter;
    
    /**
     * Resource name normalizer
     * 
     * @var ResourceNormalizerInterface
     */
    private $normalizer;
    
    /**
     * Current transaction actions
     * 
     * @var \Closure[]|null
     */
    private $transaction;
    
    /**
     * Prefix applied to all stored keys
     * 
     * @var string
     */
    private const POOL_NAMESPACE = "ness_lock_token_pool";
    
    /**
     * Initialize lock token pool
     * 
     * @param LockTokenStoreAdapterInterface $adapter
     *   Store adapter
     * @param ResourceNormalizerInterface $normalizer
     *   Resource name normalizer
     */
    public function __construct(LockTokenStoreAdapterInterface $adapter, ResourceNormalizerInterface $normalizer)
    {
        $this->adapter = $adapter;
        $this->normalizer = $normalizer;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\LockTokenPoolInterface::getToken()
     */
    public function getToken(LockableResourceInterface $resource): ?LockToken
    {
        if(null !== $token = $this->adapter->get($this->namespace($this->normalizer->normalize($resource->getLockableName()))))
            return LockToken::createFromJson($token);
        
        if(null == $resource->getLockableHierarchy())
            return null;
            
        foreach (new \RecursiveIteratorIterator(new HierarchyRecursiveIterator($resource), \RecursiveIteratorIterator::SELF_FIRST) as $resourceName => $resource) {
            if(null !== $token = $this->adapter->get($this->namespace($this->normalizer->normalize($resourceName))))
                return LockToken::createFromJson($token);
        }
        
        return null;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\LockTokenPoolInterface::saveToken()
     */
    public function saveToken(LockToken $token, LockableResourceInterface $resource): bool
    {
        $name = $this->namespace($this->normalizer->normalize($resource->getLockableName()));
        $adapterResult = $this->adapter->add($name, \json_encode($token), $token->getValidity()->getTimestamp() - \time());
        
        if(null === $hierarchy = $resource->getLockableHierarchy())
            return $adapterResult;
            
        $restoration = function(string $key): bool {
            if(null !== $token = $this->adapter->get($key)) {
                $token = LockToken::createFromJson($token);
                return $this->adapter->add($key, \json_encode($token), $token->getValidity()->getTimestamp() - \time());
            }
            
            return $this->adapter->remove($key);
        };
            
        $this->addToTransaction($name, $restoration);
        
        return $this->handleHierarchy($this->extractHierarchy($resource), function(string $key, string $normalized) use ($token): bool {
            return $this->adapter->add(
                $normalized, 
                \json_encode(LockToken::copy($key, $token)), 
                $token->getValidity()->getTimestamp() - \time());
        }, $restoration);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Storage\LockTokenPoolInterface::deleteToken()
     */
    public function deleteToken(LockableResourceInterface $resource): bool
    {
        $name = $this->namespace($this->normalizer->normalize($resource->getLockableName()));
        
        if(null === $token = $this->adapter->get($name))
            return false;
        
        $adapterResult = $this->adapter->remove($name);
        if(null === $hierarchy = $resource->getLockableHierarchy())
            return $adapterResult;

        $this->addToTransaction($name, function(string $key) use ($token): bool {
            $token = LockToken::createFromJson($token);
            return $this->adapter->add($key, \json_encode($token), $token->getValidity()->getTimestamp() - \time());
        });
        
        return $this->handleHierarchy($this->extractHierarchy($resource), function(string $key, string $normalized): bool {
            return $this->adapter->remove($normalized); 
        }, function(string $key): bool {
            $token = LockToken::createFromJson($this->adapter->get($key));
            return $this->adapter->add($key, \json_encode($token), $token->getValidity()->getTimestamp() - \time());
        });
    }
    
    /**
     * Handle a resource hierarchy
     * 
     * @param array $hierarchy
     *   Hierarchy to handle
     * @param \Closure $action
     *   Action to perform on each resource. Takes a first parameter the raw resource name and as second its normalized form
     * @param \Closure $reverse
     *   Action to perform when an error happen. Takes as single parameter the normalized key
     *   
     * @return bool
     *   Will return true if all actions have been performed with success. False if an error happen BUT pool was able to restore to its original state
     * 
     * @throws TokenPoolTransactionErrorException
     *   When the pool was not able to restore its state when an error happen
     */
    private function handleHierarchy(array $hierarchy, \Closure $action, \Closure $reverse): bool
    {
        foreach ($hierarchy as $sideResource) {
            $normalized = $this->namespace($this->normalizer->normalize($sideResource));
            if(true === $action->call($this, $sideResource, $normalized)) {
                $this->addToTransaction($normalized, $reverse);
            } else {
                if(null !== $keys = $this->reverse()) {
                    $exception = new TokenPoolTransactionErrorException("Token pool state failed to be restored. You can check keys implied by this error via the exception");
                    foreach ($keys as $key)
                        $exception->addKey($key);
                        
                    throw $exception;
                }
                
                return false;
            }
        }
        
        $this->transaction = null;
        
        return true;
    }
    
    /**
     * Extract all resource names for a hierarchical resource
     * 
     * @param LockableResourceInterface $resource
     *   Resource which to extract the hierarchy
     * 
     * @return string[]
     *   All resource names associated to the given resource
     */
    private function extractHierarchy(LockableResourceInterface $resource): array
    {
        $resources = [];
        foreach (new \RecursiveIteratorIterator(new HierarchyRecursiveIterator($resource), \RecursiveIteratorIterator::SELF_FIRST) as $name => $resource) {
            $resources[] = $name; 
        }

        return \array_unique($resources);
    }
    
    /**
     * Add an action into the transaction store
     * 
     * @param string $key
     *   Key for the action
     * @param \Closure $action
     *   Action to set. This action MUST return a boolean
     */
    private function addToTransaction(string $key, \Closure $action): void
    {
        $this->transaction[$key] = $action;
    }
    
    /**
     * Try to reverse a set of actions setted into the transaction and free the transaction from all actions
     * 
     * @return string[]|null
     *   Each keys which failed to be reversed. Or null if reverse has been made successfully
     */
    private function reverse(): ?array
    {
        foreach ($this->transaction as $key => $action) {
            if(true === $action->call($this, $key)) {
                unset($this->transaction[$key]);
            }
        }
        
        $result = empty($this->transaction) ? null : \array_keys($this->transaction);
        $this->transaction = null;
        
        return $result;
    }
    
    /**
     * Namespace a key
     * 
     * @param string $key
     *   Key to namespace
     *  
     * @return string
     *   Namespaced key
     */
    private function namespace(string $key): string
    {
        return self::POOL_NAMESPACE.$key;
    }
    
}
