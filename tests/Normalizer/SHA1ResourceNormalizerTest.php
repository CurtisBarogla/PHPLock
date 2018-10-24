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
 
namespace NessTest\Component\Lockey\Normalizer;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Normalizer\SHA1ResourceNormalizer;

/**
 * SHA1ResourceNormalizer testcase
 * 
 * @see \Ness\Component\Lockey\Normalizer\SHA1ResourceNormalizer
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SHA1ResourceNormalizerTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Normalizer\SHA1ResourceNormalizer::normalize()
     */
    public function testNormalize(): void
    {
        $normalizer = new SHA1ResourceNormalizer();
        
        $normalized = $normalizer->normalize("FooResource_@|-*/");
        
        $this->assertTrue(\strlen($normalized) <= 42);
        $this->assertTrue(1 === \preg_match("#^[A-Za-z0-9]+$#", $normalized));
    }
    
}
