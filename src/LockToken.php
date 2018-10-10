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
 * Used to identify a resource locked and its user assigned.
 * This token can be stored under serialized format or json format
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class LockToken implements \Serializable, \JsonSerializable
{
    
    /**
     * Resource identifier which the token is attributed
     * 
     * @var string
     */
    private $resource;
    
    /**
     * A unique value representing the token
     * 
     * @var string
     */
    private $value;
    
    /**
     * Timestamp which the token is considered expired
     * 
     * @var int
     */
    private $expiresAt;
    
    /**
     * Index from json representing resource name
     * 
     * @var int
     */
    private const JSON_RESOURCE_INDEX = 0;
    
    /**
     * Index from json representing token value
     *
     * @var int
     */
    private const JSON_VALUE_INDEX = 1;
    
    /**
     * Index from json representing timestamp
     *
     * @var int
     */
    private const JSON_GENERATED_INDEX = 2;
    
    /**
     * Initialize a new lock token
     * 
     * @param string $resource
     *   Resource name which the token is attributed
     * @param string $value
     *   Token unique value
     */
    public function __construct(string $resource, string $value)
    {
        $this->resource = $resource;
        $this->value = $value;
    }
    
    /**
     * Get resource which the token is attribute
     * 
     * @return string
     *   Resource name
     */
    public function getResource(): string
    {
        return $this->resource;
    }
    
    /**
     * Get the expiration time as timestamp
     * 
     * @return int
     *   Expiration time as timestamp
     */
    public function getExpiration(): int
    {
        return $this->expiresAt;
    }
    
    /**
     * Set the expiration time of the lock token
     * 
     * @param \DateInterval $interval
     *   Interval which the lock token is valid
     */
    public function setExpiration(\DateInterval $interval): void
    {
        $this->expiresAt = (new \DateTime())->add($interval)->getTimestamp();
    }
    
    /**
     * {@inheritDoc}
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return \serialize([
            $this->resource,
            $this->value,
            $this->expiresAt
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list($this->resource, $this->value, $this->expiresAt) = \unserialize($serialized);
    }

    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return [
            $this->resource,
            $this->value,
            $this->expiresAt
        ];
    }
    
    /**
     * Initialize a new token from its json representation
     * 
     * @param string $json
     *   Json lock token representation
     *   
     * @return LockToken
     *   Lock token restored
     *   
     * @throws \RuntimeException
     *   When token cannot be restored
     */
    public static function createLockTokenFromJson(string $json): LockToken
    {
        $json = \json_decode($json, true);
        
        if(null === $json)
            throw new \RuntimeException("Error when restoring lock token from his json representation");
        
        $token = new self($json[self::JSON_RESOURCE_INDEX], $json[self::JSON_VALUE_INDEX]);
        $token->expiresAt = $json[self::JSON_GENERATED_INDEX];
        
        return $token;
    }
    
}
