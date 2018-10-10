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
 
namespace NessTest\Component\Lockey;

use Ness\Component\Lockey\LockToken;

/**
 * LockToken testcase
 * 
 * @see \Ness\Component\Lockery\LockToken
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getResource()
     */
    public function testGetResource(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $this->assertSame("FooResource", $token->getResource());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getExpiration()
     */
    public function testGetExpiration(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $token->setExpiration(new \DateInterval("PT20S"));
        
        $this->assertSame(\time() + 20, $token->getExpiration());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::setExpiration()
     */
    public function testSetExpiration(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $this->assertNull($token->setExpiration(new \DateInterval("PT20S")));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::serialize()
     */
    public function testSerialize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $this->assertNotFalse(\serialize($token));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::unserialize()
     */
    public function testUnserialize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $serialized = \serialize($token);
        
        $this->assertEquals($token, \unserialize($serialized));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $this->assertNotFalse(\json_encode($token));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::createLockTokenFromJson()
     */
    public function testCreateLockTokenFromJson(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        
        $jsonsified = \json_encode($token);
        
        $this->assertEquals(LockToken::createLockTokenFromJson($jsonsified), $token);
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\LockToken::createLockTokenFromJson()
     */
    public function testExceptionCreateLockTokenFromJsonWhenGivenJsonIsCorrupted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Error when restoring lock token from his json representation");
        
        LockToken::createLockTokenFromJson("Foo");
    }
    
}
