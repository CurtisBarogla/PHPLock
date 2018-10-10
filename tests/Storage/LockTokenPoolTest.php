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
 
namespace NessTest\Component\Lockey\Storage;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Storage\LockTokenPool;
use Ness\Component\Lockey\Storage\Identity\TokenIdentityStorageInterface;
use Ness\Component\Lockey\Storage\Resource\TokenResourceStorageInterface;
use Ness\Component\Lockey\Format\LockTokenFormatterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\LockToken;

/**
 * LockTokenPool testcase
 * 
 * @see \Ness\Component\Lockery\Storage\LockTokenPool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenPoolTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::addToken()
     */
    public function testAddToken(): void
    {
        $token = new LockToken("FooResource", "FooBar");
        $token->setExpiration(new \DateInterval("PT20S"));
        
        $action = function(MockObject $identityStore, MockObject $resourceStore, MockObject $formatter) use ($token): void {
            $formatter->expects($this->exactly(3))->method("normalize")->with($token)->will($this->returnValue("::LockToken::"));
            $identityStore->expects($this->exactly(3))->method("add")->withConsecutive(["FooResource", "::LockToken::"])->will($this->onConsecutiveCalls(true, true, false));
            $resourceStore->expects($this->exactly(2))->method("add")->withConsecutive(["FooResource", "::LockToken::", 20])->will($this->onConsecutiveCalls(true, false));
            $identityStore->expects($this->once())->method("remove")->with("FooResource");
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->addToken($token));
        $this->assertFalse($pool->addToken($token));
        $this->assertFalse($pool->addToken($token));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getToken()
     */
    public function testGetToken(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->any())->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $identityToken = new LockToken("FooResource", "FooBar");
        $resourceToken = new LockToken("FooResource", "FooBar");
        
        $action = function(MockObject $identityStore, MockObject $resourceStore, MockObject $formatter) use ($identityToken, $resourceToken): void {
            $resourceStore
                ->expects($this->exactly(4))
                ->method("get")
                ->withConsecutive(["FooResource"])
                ->will($this->onConsecutiveCalls(null, null, "::LockToken::", "::LockToken::"));
            
            $identityStore
                ->expects($this->exactly(4))
                ->method("get")
                ->withConsecutive(["FooResource"])
                ->will($this->onConsecutiveCalls(null, "::LockToken::", null, "::LockToken::"));
            
            $identityStore->expects($this->once())->method("remove")->with("FooResource");
            
            $formatter
                ->expects($this->exactly(3))
                ->method("denormalize")
                ->withConsecutive(["::LockToken::"])
                ->will($this->onConsecutiveCalls($resourceToken, $identityToken, $resourceToken));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertNull($pool->getToken($resource));
        $this->assertNull($pool->getToken($resource));
        $this->assertSame([null, $resourceToken], $pool->getToken($resource));
        $this->assertSame([$identityToken, $resourceToken], $pool->getToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::removeToken()
     */
    public function testRemoveToken(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(6))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $action = function(MockObject $identityStore, MockObject $resourceStore, MockObject $formatter) use ($resource): void {
            $identityStore
                ->expects($this->exactly(3))
                ->method("remove")
                ->withConsecutive(["FooResource"])
                ->will($this->onConsecutiveCalls(true, false, false));
            $resourceStore
                ->expects($this->exactly(3))
                ->method("remove")
                ->withConsecutive(["FooResource"])
                ->will($this->onConsecutiveCalls(true, true, false));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->removeToken($resource));
        $this->assertTrue($pool->removeToken($resource));
        $this->assertFalse($pool->removeToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::clear()
     */
    public function testClear(): void
    {
        $action = function(MockObject $identityStore, MockObject $resourceStore, MockObject $formatter): void {
            $identityStore->expects($this->once())->method("clear");
            $resourceStore->expects($this->once())->method("clear");
        };
        
        $pool = $this->getPool($action);
        
        $this->assertNull($pool->clear());
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::setFormatter()
     */
    public function testSetFormatter(): void
    {
        $formatter = $this->getMockBuilder(LockTokenFormatterInterface::class)->getMock();
        
        $pool = new LockTokenPool(
            $this->getMockBuilder(TokenIdentityStorageInterface::class)->getMock(), 
            $this->getMockBuilder(TokenResourceStorageInterface::class)->getMock());
        
        $this->assertNull($pool->setFormatter($formatter));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getFormatter()
     */
    public function testGetFormatter(): void
    {
        $formatter = $this->getMockBuilder(LockTokenFormatterInterface::class)->getMock();
        
        $pool = new LockTokenPool(
            $this->getMockBuilder(TokenIdentityStorageInterface::class)->getMock(),
            $this->getMockBuilder(TokenResourceStorageInterface::class)->getMock());
        
        $pool->setFormatter($formatter);
        
        $this->assertSame($formatter, $pool->getFormatter());
    }
    
    /**
     * Get a lock token pool with an identity store, a resource sotre and a formatter setted into it
     * 
     * @param \Closure|null $action
     *   Action to perform on the :
     *   <ul>
     *       <li>identity store</li>
     *       <li>resource store</li>
     *       <li>formatter</li>
     *   </ul>
     *  
     * @return LockTokenPool
     *   Lock token pool initialized
     */
    private function getPool(?\Closure $action): LockTokenPool
    {
        $identityStore = $this->getMockBuilder(TokenIdentityStorageInterface::class)->getMock();
        $resourceStore = $this->getMockBuilder(TokenResourceStorageInterface::class)->getMock();
        $formatter = $this->getMockBuilder(LockTokenFormatterInterface::class)->getMock();
        
        if(null !== $action) {
            $action->call($this, $identityStore, $resourceStore, $formatter);
        }
        
        $pool = new LockTokenPool($identityStore, $resourceStore);
        $pool->setFormatter($formatter);
        
        return $pool;
    }
    
}
