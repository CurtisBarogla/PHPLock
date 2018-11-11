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
 
namespace NessTest\Component\Lockey\Storage\Adapter;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Storage\Adapter\CacheItemPoolLockTokenStoreAdapter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * CacheItemPoolLockTokenStoreAdapter testcase
 * 
 * @see \Ness\Component\Lockey\Storage\Adapter\CacheItemPoolLockTokenStoreAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolLockTokenStoreAdapterTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheItemPoolLockTokenStoreAdapter::get()
     */
    public function testGet(): void
    {
        $itemFoo = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $itemFoo->expects($this->once())->method("get")->will($this->returnValue("FooResourceToken"));
        $itemFoo->expects($this->once())->method("isHit")->will($this->returnValue(true));
        
        $itemBar = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $itemBar->expects($this->once())->method("isHit")->will($this->returnValue(false));
        
        $action = function(MockObject $pool) use ($itemFoo, $itemBar): void {
            $pool
                ->expects($this->exactly(2))
                ->method("getItem")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls($itemFoo, $itemBar));   
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertSame("FooResourceToken", $adapter->get("FooResource"));
        $this->assertNull($adapter->get("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheItemPoolLockTokenStoreAdapter::add()
     */
    public function testAdd(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $item->expects($this->exactly(2))->method("set")->with("FooResourceToken")->will($this->returnSelf());
        $item->expects($this->exactly(2))->method("expiresAfter")->with(42)->will($this->returnSelf());
        
        $action = function(MockObject $pool) use ($item): void {
            $pool->expects($this->exactly(2))->method("getItem")->with("FooResource")->will($this->returnValue($item));
            $pool->expects($this->exactly(2))->method("saveDeferred")->with($item)->will($this->onConsecutiveCalls(true, false));
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertTrue($adapter->add("FooResource", "FooResourceToken", 42));
        $this->assertFalse($adapter->add("FooResource", "FooResourceToken", 42));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheItemPoolLockTokenStoreAdapter::remove()
     */
    public function testRemove(): void
    {
        $action = function(MockObject $pool): void {
            $pool
                ->expects($this->exactly(2))
                ->method("deleteItem")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls(true, false));
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertTrue($adapter->remove("FooResource"));
        $this->assertFalse($adapter->remove("BarResource"));
    }
    
    /**
     * Get an initialized CacheItemPoolLockTokenStoreAdapter with a PSR-6 Cache Pool mock setted
     *
     * @param \Closure|null
     *   Action to perform on the PSR-6 Cache Pool mock
     *
     * @return CacheItemPoolLockTokenStoreAdapter
     *   Initialized tested adapter
     */
    private function getAdapter(?\Closure $action): CacheItemPoolLockTokenStoreAdapter
    {
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $pool);
        
        return new CacheItemPoolLockTokenStoreAdapter($pool);
    }
    
}
