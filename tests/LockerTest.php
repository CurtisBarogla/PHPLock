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
 
namespace NessTest\Component\Lockery;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Storage\LockTokenPoolInterface;
use Ness\Component\Lockey\Format\LockTokenFormatterAwareInterface;
use Ness\Component\Lockey\Generator\LockTokenGeneratorInterface;
use Ness\Component\Lockey\Format\LockTokenFormatterInterface;
use Ness\Component\Lockey\Locker;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\Exception\LockErrorException;
use Ness\Component\Lockey\Exception\UnlockErrorException;
use Ness\Component\Lockey\Exception\InvalidArgumentException;

/**
 * Locker testcase
 * 
 * @see \Ness\Component\Lockery\Locker
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockerTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Locker::__construct()
     */
    public function test__constructWithPoolFormatAware(): void
    {
        $generator = $this->getMockBuilder(LockTokenGeneratorInterface::class)->getMock();
        $formatter = $this->getMockBuilder(LockTokenFormatterInterface::class)->getMock();
        
        $pool = $this->getMockBuilder([LockTokenPoolInterface::class, LockTokenFormatterAwareInterface::class])->getMock();
        $pool->expects($this->once())->method("setFormatter")->with($formatter);
        
        $locker = new Locker($formatter, $pool, $generator);
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::lock()
     */
    public function testLock(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        $generatedToken = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token, $generatedToken): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getToken")
                ->withConsecutive([$resource])
                ->will($this->onConsecutiveCalls([$token, $token], null));
            $pool->expects($this->once())->method("addToken")->with($generatedToken)->will($this->returnValue(true));
            $generator->expects($this->once())->method("generate")->will($this->returnValue($generatedToken));
        };
        
        $locker = $this->getLocker($action);
        
        $tokenInterval = new \DateInterval("PT30S");
        
        $this->assertNull($locker->lock($resource, new \DateInterval("PT30S")));
        $this->assertNull($locker->lock($resource, $tokenInterval));
        $this->assertSame((new \DateTime())->add($tokenInterval)->getTimestamp(), $generatedToken->getExpiration());
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testFree(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token): void {
            $pool->expects($this->exactly(2))->method("getToken")->withConsecutive([$resource])->will($this->onConsecutiveCalls(null, [$token, $token]));
            $pool->expects($this->once())->method("removeToken")->with($resource)->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($resource));
        $this->assertNull($locker->free($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testBypass(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token): void {
            $pool->expects($this->exactly(2))->method("getToken")->with($resource)->will($this->returnValue(null));
            $generator->expects($this->once())->method("generate")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("addToken")->with($token)->will($this->returnValue(true));
        };
        
        $interval = new \DateInterval("PT20S");
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->bypass($resource, $interval));
        $this->assertSame((new \DateTime())->add($interval)->getTimestamp(), $token->getExpiration());
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::checkLocked()
     */
    public function testCheckLocked(): void
    {
        $interval = new \DateInterval("PT20S");
        $datetime = (new \DateTime())->add($interval);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(3))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration($interval);
        $tokenTwo = new LockToken("FooResource", "BarFoo");
        $tokenTwo->setExpiration($interval);
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($token, $tokenTwo, $resource): void {
            $pool
                ->expects($this->exactly(3))
                ->method("getToken")
                ->withConsecutive([$resource])
                ->will($this->onConsecutiveCalls(null, [$token, $token], [$token, $tokenTwo]));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->checkLocked($resource));
        $this->assertNull($locker->checkLocked($resource));
        $this->assertEquals(\DateTimeImmutable::createFromMutable($datetime)->getTimestamp(), $locker->checkLocked($resource)->getTimestamp());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\Locker::lock()
     */
    public function testExceptionLockWhenPoolFailsToStoreTheToken(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("An error happen when trying to lock this resource : 'FooResource'");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource): void {
            $token = new LockToken("FooResource", "FooBar");
            $generator->expects($this->once())->method("generate")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue(null));
            $pool->expects($this->once())->method("addToken")->with($token)->will($this->returnValue(false));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->lock($resource, new \DateInterval("PT20S"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenTokensNotEquals(): void
    {
        $this->expectException(UnlockErrorException::class);
        $this->expectExceptionMessage("Lock Token assigned has been expired for resource 'FooResource'");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        $token = new LockToken("FooResource", "FooBar");
        $tokenTwo = new LockToken("FooResource", "BarFoo");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token, $tokenTwo): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue([$token, $tokenTwo]));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenPoolFailsToRemoveToken(): void
    {
        $this->expectException(UnlockErrorException::class);
        $this->expectExceptionMessage("An error happen when trying to unlock this resource : 'FooResource'");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        $token = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue([$token , $token]));
            $pool->expects($this->once())->method("removeToken")->with($resource)->will($this->returnValue(false));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testExceptionBypassWithNoPreviousTokenToRestoreWhenTokenResourceCannotBeLocked(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("An error happen when trying to bypass current lock on resource 'FooResource'. No previous lock token has been found though.");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getToken")
                ->withConsecutive([$resource])
                ->will($this->onConsecutiveCalls(null));
            $generator->expects($this->once())->method("generate")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("addToken")->with($token)->will($this->returnValue(false));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->bypass($resource, new \DateInterval("PT20M"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testExceptionBypassWithPreviousTokenToRestoreWhenTokenResourceCannotBeLockedWhenOldTokenIsStoredWithSuccess(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("An error happen when trying to bypass current lock on resource 'FooResource'. The previous lock token has been restored with success.");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        $oldToken = new LockToken("FooResource", "BarFoo");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token, $oldToken): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getToken")
                ->withConsecutive([$resource])
                ->will($this->onConsecutiveCalls([$oldToken, $oldToken], null));
            $generator->expects($this->once())->method("generate")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("removeToken")->with($resource)->will($this->returnValue(true));
            $pool->expects($this->exactly(2))->method("addToken")->withConsecutive([$token], [$oldToken])->will($this->onConsecutiveCalls(false, true));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->bypass($resource, new \DateInterval("PT20M"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testExceptionBypassWithPreviousTokenToRestoreWhenTokenResourceCannotBeLockedWhenOldTokenCannotBeStored(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("Bypassing on resource 'FooResource' failed and the current lock token cannot be restored as LockTokenPool was not able to restore it.");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $token = new LockToken("FooResource", "FooBar");
        $oldToken = new LockToken("FooResource", "BarFoo");
        
        $action = function(MockObject $pool, MockObject $generator, MockObject $formatter) use ($resource, $token, $oldToken): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getToken")
                ->withConsecutive([$resource])
                ->will($this->onConsecutiveCalls([$oldToken, $oldToken], null));
            $generator->expects($this->once())->method("generate")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("removeToken")->with($resource)->will($this->returnValue(true));
            $pool->expects($this->exactly(2))->method("addToken")->withConsecutive([$token], [$oldToken])->will($this->onConsecutiveCalls(false, false));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->bypass($resource, new \DateInterval("PT20M"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::checkLocked()
     */
    public function testExceptionWhenResourceNameIsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This resource name 'HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH' is invalid ! Resource name MUST not be greater than 31 characters");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableResourceName")->will($this->returnValue(\str_repeat('H', 32)));
        
        $locker = $this->getLocker(null);
        
        $locker->checkLocked($resource);
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::checkLocked()
     */
    public function testExceptionWhenResourceContainsInvalidCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This resource name 'FooResource@' is invalid ! Resource name MUST respect [A-Za-z0-9_.-] pattern");
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableResourceName")->will($this->returnValue("FooResource@"));
        
        $locker = $this->getLocker(null);
        
        $locker->checkLocked($resource);
    }
    
    /**
     * Get an instance of tested Locked
     * 
     * @param \Closure|null $action
     *   Action to perform on : 
     *   <ul>
     *      <li>pool</li>
     *      <li>generator</li>
     *      <li>formatter</li>
     *   </ul>
     *   
     * @return Locker
     *   Locker initialized
     */
    private function getLocker(?\Closure $action): Locker
    {
        $pool = $this->getMockBuilder(LockTokenPoolInterface::class)->getMock();
        $generator = $this->getMockBuilder(LockTokenGeneratorInterface::class)->getMock();
        $formatter = $this->getMockBuilder(LockTokenFormatterInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $pool, $generator, $formatter);
        
        return new Locker($formatter, $pool, $generator);
    }
    
}
