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
 
namespace Ness\Component\Lockey\Normalizer;

/**
 * Normalize a resource name to make it storable
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceNormalizerInterface
{
    
    /**
     * Normalize a resource name
     * Resource name normalize MUST comply [A-Za-z0-9] pattern and lenght MUST be between 3 and 42 characters
     * 
     * @param string $resource
     *   Resource name to normalize
     */
    public function normalize(string $resource): string;
    
}
