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
 
namespace NessTest\Component\Lockey\Formatter;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\Format\NativeLockTokenFormatter;
use Ness\Component\Lockey\Exception\FormatterException;

/**
 * NativeLockTokenFormatter testcase
 * 
 * @see \Ness\Component\Lockery\Format\NativeLockTokenFormatter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeLockTokenFormatterTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Format\NativeLockTokenFormatter::normalize()
     */
    public function testNormalize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration(new \DateInterval("PT10M"));
        
        $formatter = new NativeLockTokenFormatter();
        
        $expiration = \time() + 600;
        
        $this->assertSame('C:31:"Ness\Component\Lockey\LockToken":63:{a:3:{i:0;s:11:"FooResource";i:1;s:6:"FooBar";i:2;i:' . $expiration . ';}}', $formatter->normalize($token));
    }
    
    /**
     * @see \Ness\Component\Lockey\Format\NativeLockTokenFormatter::denormalize()
     */
    public function testDenormalize(): void
    {
        $formatter = new NativeLockTokenFormatter();
        
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration(new \DateInterval("PT10M"));
        
        $normalized = $formatter->normalize($token); 

        $this->assertEquals($token, $formatter->denormalize($normalized));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\Format\NativeLockTokenFormatter::denormalize()
     */
    public function testExceptionDenormalizeWhenStartCharsAreInvalid(): void
    {
        $this->expectException(FormatterException::class);
        
        $formater = new NativeLockTokenFormatter();
        
        $formater->denormalize("Foo");
    }
    
    /**
     * @see \Ness\Component\Lockey\Format\NativeLockTokenFormatter::denormalize()
     */
    public function testExceptionDenormalizeWhenStartCharsAreValidButTokenIsCorrupted(): void
    {
        $this->expectException(FormatterException::class);
        
        $formater = new NativeLockTokenFormatter();
        
        $formater->denormalize("C:Foo");
    }
    
}
