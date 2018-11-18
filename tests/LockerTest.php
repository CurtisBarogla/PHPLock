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

use Ness\Component\Lockey\Locker;
use Ness\Component\Lockey\Storage\LockTokenPoolInterface;
use Ness\Component\User\UserInterface;
use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\LockableResourceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Lockey\Exception\LockErrorException;
use Ness\Component\Lockey\Exception\LockTokenExpiredException;
use Ness\Component\Lockey\Exception\TokenPoolTransactionErrorException;
use Ness\Component\Lockey\Exception\UnlockErrorException;

/**
 * Locker testcase
 * 
 * @see \Ness\Component\Lockey\Locker
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockerTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Locker::getState()
     */
    public function testGetState(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
        };
        
        $locker = $this->getLocker($action);
        
        $state = $locker->getState($user, $resource);
        
        $this->assertSame($token, $state->getToken());
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::exclusive()
     */
    public function testExclusive(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));

        $action = function(MockObject $pool) use ($resource, $token): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getToken")
                ->with($resource)
                ->will($this->onConsecutiveCalls(new LockToken("FooResource", LockToken::EXCLUSIVE), null));
            $pool
                ->expects($this->once())
                ->method("saveToken")
                ->with($token, $resource)
                ->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->exclusive($user, $resource, new \DateInterval("PT20S")));
        $this->assertNull($locker->exclusive($user, $resource, new \DateInterval("PT20S")));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::share()
     */
    public function testShare(): void
    {
        $userFoo = $this->getMockBuilder(UserInterface::class)->getMock();
        $userFoo->expects($this->exactly(4))->method("getName")->will($this->returnValue("FooUser"));
        $userBar = $this->getMockBuilder(UserInterface::class)->getMock();
        $userBar->expects($this->exactly(4))->method("getName")->will($this->returnValue("BarUser"));
        
        $tokenShare = new LockToken("FooResource", LockToken::SHARE);
        $tokenShare->setValidity(new \DateInterval("PT20S"));
        $tokenShare->setMaster($userFoo);
        $tokenShare->shareWith($userBar);
        
        $tokenFull = new LockToken("FooResource", LockToken::FULL);
        $tokenFull->setValidity(new \DateInterval("PT20S"));
        $tokenFull->setMaster($userFoo);
        $tokenFull->shareWith($userBar);
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool) use ($resource, $tokenShare, $tokenFull): void {
            $pool
                ->expects($this->exactly(3))
                ->method("getToken")
                ->with($resource)
                ->will($this->onConsecutiveCalls(new LockToken("FooResource", LockToken::EXCLUSIVE), null, null));
            $pool
                ->expects($this->exactly(2))
                ->method("saveToken")
                ->withConsecutive(
                    [$tokenShare, $resource],
                    [$tokenFull, $resource]
                )
                ->will($this->onConsecutiveCalls(true, true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->share($userFoo, [$userBar], $resource, new \DateInterval("PT20S")));
        $this->assertNull($locker->share($userFoo, [$userBar], $resource, new \DateInterval("PT20S")));
        $this->assertNull($locker->share($userFoo, [$userBar], $resource, new \DateInterval("PT20S"), true));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testFreeWhenExclusive(): void
    {
        $this->expectOutputString("Resource Updated");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        
        $phpUnit = $this;
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("deleteToken")->with($resource)->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($user, $resource, function() use ($phpUnit, $resource): void {
            $phpUnit->assertSame($resource, $this);
            echo "Resource Updated";
        }));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testFreeWhenShared(): void
    {
        $this->expectOutputString("Resource Updated");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $token = new LockToken("FooResource", LockToken::SHARE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        
        $phpUnit = $this;
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("deleteToken")->with($resource)->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($user, $resource, function() use ($phpUnit, $resource): void {
            $phpUnit->assertSame($resource, $this);
            echo "Resource Updated";
        }));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testFreeWhenFullyShared(): void
    {
        $this->expectOutputString("Resource UpdatedResource Updated");
        
        $userFoo = $this->getMockBuilder(UserInterface::class)->getMock();
        $userFoo->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $userBar = $this->getMockBuilder(UserInterface::class)->getMock();
        $userBar->expects($this->exactly(3))->method("getName")->will($this->returnValue("BarUser"));
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        
        $token = new LockToken("FooResource", LockToken::FULL);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($userFoo);
        $token->shareWith($userBar);
        
        $phpUnit = $this;
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->exactly(2))->method("getToken")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->exactly(2))->method("deleteToken")->with($resource)->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($userFoo, $resource, function() use ($phpUnit, $resource): void {
            $phpUnit->assertSame($resource, $this);
            echo "Resource Updated";
        }));
        $this->assertNull($locker->free($userBar, $resource, function() use ($phpUnit, $resource): void {
            $phpUnit->assertSame($resource, $this);
            echo "Resource Updated";
        }));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testBypass(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool) use ($resource, $token): void {
            $pool
                ->expects($this->once())
                ->method("saveToken")
                ->with($token, $resource)
                ->will($this->returnValue(true));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->bypass($user, $resource, new \DateInterval("PT20S")));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\Locker::exclusive()
     * @see \Ness\Component\Lockey\Locker::share()
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testExceptionLockWhenPoolCannotSaveTokenAndReverseWithSuccess(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("An error happen when assigning a lock on resource 'FooResource'. Change has been reverted successfully");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue(null));
            $pool->expects($this->once())->method("saveToken")->with($token, $resource)->will($this->returnValue(false));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->exclusive($user, $resource, new \DateInterval("PT20S"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::exclusive()
     * @see \Ness\Component\Lockey\Locker::share()
     * @see \Ness\Component\Lockey\Locker::bypass()
     */
    public function testExceptionLockWhenPoolCannotSaveTokenAndFailedToReverse(): void
    {
        $this->expectException(LockErrorException::class);
        $this->expectExceptionMessage("An error happen when assigning a lock on resource 'FooResource'. Token pool failed to restore its original state. See 'FooResource, BarResource' keys that might be inconsistent");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $exception = new TokenPoolTransactionErrorException();
        $exception->addKey("FooResource");
        $exception->addKey("BarResource");
        
        $action = function(MockObject $pool) use ($token, $resource, $exception): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue(null));
            $pool->expects($this->once())->method("saveToken")->with($token, $resource)->will($this->throwException($exception));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->exclusive($user, $resource, new \DateInterval("PT20S"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::share()
     */
    public function testExceptionFreeWhenNoToken(): void
    {
        $this->expectException(LockTokenExpiredException::class);
        $this->expectExceptionMessage("Lock on resource 'FooResource' cannot be revoked as not previous lock has been assigned to it");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool) use ($user, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue(null));
        };
        
        $locker = $this->getLocker($action);
        
        $this->assertNull($locker->free($user, $resource, function(): void {}));
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenNotMasterOnExclusiveToken(): void
    {
        $this->expectException(LockTokenExpiredException::class);
        $this->expectExceptionMessage("Your token has been revoked or is invalid. You MUST be setted as master to update the resource : 'FooResource'");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $userGiven = $this->getMockBuilder(UserInterface::class)->getMock();
        $userGiven->expects($this->once())->method("getName")->will($this->returnValue("BarUser"));
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->free($userGiven, $resource, function(): void {});
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenNotMasterOnSharedToken(): void
    {
        $this->expectException(LockTokenExpiredException::class);
        $this->expectExceptionMessage("Your token has been revoked or is invalid. You MUST be setted as master to update the resource : 'FooResource'");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::SHARE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $userGiven = $this->getMockBuilder(UserInterface::class)->getMock();
        $userGiven->expects($this->once())->method("getName")->will($this->returnValue("BarUser"));
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->free($userGiven, $resource, function(): void {});
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenNotInSharedListOnFullySharedToken(): void
    {
        $this->expectException(LockTokenExpiredException::class);
        $this->expectExceptionMessage("Your token has been revoked or is invalid. You MUST be setted as master or sharing the lock with 'FooUser' to update the resource : 'FooResource'");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::FULL);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $userGiven = $this->getMockBuilder(UserInterface::class)->getMock();
        $userGiven->expects($this->exactly(2))->method("getName")->will($this->returnValue("BarUser"));
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->free($userGiven, $resource, function(): void {});
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenPoolCannotSaveTokenAndReverseWithSuccess(): void
    {
        $this->expectException(UnlockErrorException::class);
        $this->expectExceptionMessage("Lock token cannot be revoked for resource 'FooResource'. Lock token has been restored with sucess. Action has been canceled");
        $this->expectOutputString("");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $pool) use ($token, $resource): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("deleteToken")->with($resource)->will($this->returnValue(false));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->free($user, $resource, function(): void {
            echo "Resource Updated";
        });
    }
    
    /**
     * @see \Ness\Component\Lockey\Locker::free()
     */
    public function testExceptionFreeWhenPoolCannotSaveTokenAndFailedToReverse(): void
    {
        $this->expectException(UnlockErrorException::class);
        $this->expectExceptionMessage("An error happen when revoking a lock on resource 'FooResource'. Token pool failed to restore to its original state. See 'FooResource, BarResource' keys that might be inconsistent. Action has been canceled.");
        $this->expectOutputString("");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooUser"));
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token->setMaster($user);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        
        $exception = new TokenPoolTransactionErrorException();
        $exception->addKey("FooResource");
        $exception->addKey("BarResource");
        
        $action = function(MockObject $pool) use ($token, $resource, $exception): void {
            $pool->expects($this->once())->method("getToken")->with($resource)->will($this->returnValue($token));
            $pool->expects($this->once())->method("deleteToken")->with($resource)->will($this->throwException($exception));
        };
        
        $locker = $this->getLocker($action);
        
        $locker->free($user, $resource, function(): void {
            echo "Resource Updated";
        });
    }
    
    /**
     * Get an initialized Locker
     * 
     * @param \Closure $action
     *   Action to perform on the token pool
     * 
     * @return Locker
     *   Initializer locker
     */
    private function getLocker(?\Closure $action): Locker
    {
        $pool = $this->getMockBuilder(LockTokenPoolInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $pool);
        
        return new Locker($pool);
    }
    
}
