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
 
namespace NessTest\Component\Lockey;

use Ness\Component\Lockey\LockToken;
use Ness\Component\User\UserInterface;
use Ness\Component\Lockey\Exception\LockTokenCorruptedException;

/**
 * LockToken testcase
 * 
 * @see \Ness\Component\Lockey\LockToken
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\LockToken::setValidity()
     */
    public function setValidity(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $this->assertNull($token->setValidity(new \DateInterval("PT20S")));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getValidity()
     */
    public function testGetValidity(): void
    {
        $validity = new \DateInterval("PT20S");
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $token->setValidity($validity);
        
        $this->assertEquals((new \DateTime())->add($validity)->getTimestamp(), $token->getValidity()->getTimestamp());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getResource()
     */
    public function testGetResource(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $this->assertSame("FooResource", $token->getResource());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getType()
     */
    public function testGetType(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $this->assertSame(LockToken::EXCLUSIVE, $token->getType());
        
        $token = new LockToken("FooResource", LockToken::SHARE);
        
        $this->assertSame(LockToken::SHARE, $token->getType());
        
        $token = new LockToken("FooResource", LockToken::FULL);
        
        $this->assertSame(LockToken::FULL, $token->getType());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::setMaster()
     */
    public function testSetMaster(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $this->assertNull($token->setMaster($user));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::getMaster()
     */
    public function testGetMaster(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $token->setMaster($user);
        
        $this->assertSame("FooUser", $token->getMaster());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::isMaster()
     */
    public function testIsMaster(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(3))->method("getName")->will($this->onConsecutiveCalls("FooUser", "FooUser", "BarUser"));
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $token->setMaster($user);
        
        $this->assertTrue($token->isMaster($user));
        $this->assertFalse($token->isMaster($user));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::shareWith()
     */
    public function testShareWith(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->onConsecutiveCalls("FooUser", "BarUser"));
        
        $token = new LockToken("FooResource", LockToken::FULL);
        $token->setMaster($user);
        $this->assertNull($token->shareWith($user));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::isSharedWith()
     */
    public function testIsSharedWith(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $this->assertFalse($token->isSharedWith($user));
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(4))->method("getName")->will($this->onConsecutiveCalls("FooUser", "BarUser", "BarUser", "MozUser"));
        
        $token = new LockToken("FooResource", LockToken::FULL);
        $token->setMaster($user);
        $token->shareWith($user);
        
        $this->assertTrue($token->isSharedWith($user));
        $this->assertFalse($token->isSharedWith($user));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::copy()
     */
    public function testCopy(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($this->getMockBuilder(UserInterface::class)->getMock());
        
        $copy = LockToken::copy("BarResource", $token);
        
        $this->assertSame("BarResource", $copy->getResource());
        $this->assertEquals($token->getValidity(), $copy->getValidity());
        $this->assertSame($token->getMaster(), $copy->getMaster());
        $this->assertSame($token->getType(), $copy->getType());
        
        $userFoo = $this->getMockBuilder(UserInterface::class)->getMock();
        $userFoo->expects($this->any())->method("getName")->will($this->returnValue("FooUser"));
        
        $userBar = $this->getMockBuilder(UserInterface::class)->getMock();
        $userBar->expects($this->any())->method("getName")->will($this->returnValue("BarUser"));
        
        $token = new LockToken("FooResource", LockToken::SHARE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($userFoo);
        $token->shareWith($userBar);
        
        $copy = LockToken::copy("BarResource", $token);
        
        $this->assertSame("BarResource", $copy->getResource());
        $this->assertEquals($token->getValidity(), $copy->getValidity());
        $this->assertSame($token->getMaster(), $copy->getMaster());
        $this->assertSame($token->getType(), $copy->getType());
        $this->assertSame($token->isSharedWith($userBar), $copy->isSharedWith($userBar));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        
        $this->assertNotFalse(\json_encode($token));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::createFromJson()
     */
    public function testCreateFromJson(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(5))->method("getName")->will($this->returnValue("FooUser", "BarUser", "MozUser", "BarUser", "MozUser"));
        
        $validity = new \DateInterval("PT20S");
        
        $token = new LockToken("FooResource", LockToken::SHARE);
        $token->setValidity($validity);
        $token->setMaster($user);
        $token->shareWith($user);
        $token->shareWith($user);
        
        $json = \json_encode($token);
        
        $token = LockToken::createFromJson($json);
        
        $this->assertSame("FooResource", $token->getResource());
        $this->assertSame("FooUser", $token->getMaster());
        $this->assertTrue($token->isSharedWith($user));
        $this->assertTrue($token->isSharedWith($user));
        $this->assertSame(LockToken::SHARE, $token->getType());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\LockToken::setValidity()
     */
    public function testExceptionSetValidityWhenImmutable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This lock token for resource 'FooResource' is in an immutable state and therefore cannot be updated");
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        
        $token = \json_encode($token);
        $token = LockToken::createFromJson($token);
        
        $token->setValidity(new \DateInterval("PT20S"));
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::setMaster()
     */
    public function testExceptionSetMasterWhenImmutable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This lock token for resource 'FooResource' is in an immutable state and therefore cannot be updated");
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This lock token for resource 'FooResource' is in an immutable state and therefore cannot be updated");
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        
        $token = \json_encode($token);
        $token = LockToken::createFromJson($token);
        
        $token->setMaster($this->getMockBuilder(UserInterface::class)->getMock());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::shareWith()
     */
    public function testExceptionShareWithWhenImmutable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This lock token for resource 'FooResource' is in an immutable state and therefore cannot be updated");
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        
        $token = \json_encode($token);
        $token = LockToken::createFromJson($token);
        
        $token->shareWith($this->getMockBuilder(UserInterface::class)->getMock());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::shareWith()
     */
    public function testExceptionShareWithWhenNotMasterDefined(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No master has been defined for this lock token. Therefore no user can been added to the share list");
        
        $token = new LockToken("FooResource", LockToken::SHARE);
        
        $token->shareWith($this->getMockBuilder(UserInterface::class)->getMock());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::shareWith()
     */
    public function testExceptionShareWithWhenTokenIsExclusive(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This token cannot be shared as it is not declared as if");
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setMaster($this->getMockBuilder(UserInterface::class)->getMock());
        
        $token->shareWith($this->getMockBuilder(UserInterface::class)->getMock());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockToken::createFromJson()
     */
    public function testExceptionCreateFromJsonWhenJsonIsCorrupted(): void
    {
        $this->expectException(LockTokenCorruptedException::class);
        $this->expectExceptionMessage("Token cannot be restored from his json representation");
        
        LockToken::createFromJson("foo");
    }
    
}
