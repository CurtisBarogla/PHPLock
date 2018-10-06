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
 
namespace NessTest\Component\Lockery\Formatter;

use NessTest\Component\Lockery\LockeryTestCase;
use Ness\Component\Lockery\LockToken;
use Ness\Component\Lockery\Format\JsonLockTokenFormatter;
use Ness\Component\Lockery\Exception\FormatterException;

/**
 * JsonLockTokenFormatter testcase
 * 
 * @see \Ness\Component\Lockery\Format\JsonLockTokenFormatter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonLockTokenFormatterTest extends LockeryTestCase
{
    
    /**
     * @see \Ness\Component\Lockery\Format\JsonLockTokenFormatter::normalize()
     */
    public function testNormalize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration(new \DateInterval("PT10M"));
        
        $normalizer = new JsonLockTokenFormatter();
        
        $expiration = \time() + 600;
        
        $this->assertSame('["FooResource","FooBar",' . $expiration . ']', $normalizer->normalize($token));
    }
    
    /**
     * @see \Ness\Component\Lockery\Format\JsonLockTokenFormatter::denormalize()
     */
    public function testDenormalize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration(new \DateInterval("PT10M"));
        $normalizer = new JsonLockTokenFormatter();
        
        $expiration = \time() + 600;
        
        $this->assertEquals($token, $normalizer->denormalize('["FooResource","FooBar",' . $expiration . ']'));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockery\Format\JsonLockTokenFormatter::denormalize()
     */
    public function testExceptionDenormalizeWhenJsonIsCorrupted(): void
    {
        $this->expectException(FormatterException::class);
        
        $normalizer = new JsonLockTokenFormatter();
        
        $normalizer->denormalize('foo');
    }
    
}
