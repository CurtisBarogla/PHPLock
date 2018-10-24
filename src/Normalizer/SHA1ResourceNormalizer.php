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
 * Simply apply a SHA1 on the resource name.
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SHA1ResourceNormalizer implements ResourceNormalizerInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Lockey\Normalizer\ResourceNormalizerInterface::normalize()
     */
    public function normalize(string $resource): string
    {
        return \hash("sha1", $resource);
    }
    
}
