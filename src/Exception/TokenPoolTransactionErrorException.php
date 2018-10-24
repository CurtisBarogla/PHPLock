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
 
namespace Ness\Component\Lockey\Exception;

/**
 * When an error happen when restoring a pool to its original state
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TokenPoolTransactionErrorException extends \Exception
{
    
    /**
     * Token key
     * 
     * @var string[]
     */
    private $keys;
    
    /**
     * Add a key which represent a token corresponding to the error
     * 
     * @param string $key
     *   Key token
     */
    public function addKey(string $key): void
    {
        $this->keys[] = $key;
    }
    
    /**
     * Get the list of all keys corresponding to the error
     * 
     * @return array|null
     *   List of token key or null when no error
     */
    public function getKeys(): ?array
    {
        return $this->keys;
    }
    
}
