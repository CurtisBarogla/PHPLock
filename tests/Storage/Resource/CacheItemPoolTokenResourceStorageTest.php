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
 
namespace NessTest\Component\Lockey\Storage\Resource;

use NessTest\Component\Lockey\LockeyTestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Lockey\Storage\Resource\CacheItemPoolTokenResourceStorage;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

/**
 * CacheItemPoolTokenResourceStorage testcase
 * 
 * @see \Ness\Component\Lockery\Storage\Resource\CacheItemPoolTokenResourceStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolTokenResourceStorageTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheItemPoolTokenResourceStorage::get()
     */
    public function testGet(): void
    {
        $hitted = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $hitted->expects($this->once())->method('isHit')->will($this->returnValue(true));
        $hitted->expects($this->once())->method("get")->will($this->returnValue("FooBar"));
        
        $notHitted = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $notHitted->expects($this->once())->method("isHit")->will($this->returnValue(false));
        
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool
            ->expects($this->exactly(2))
            ->method("getItem")
            ->withConsecutive([CacheItemPoolTokenResourceStorage::PREFIX."FooResource"], [CacheItemPoolTokenResourceStorage::PREFIX."BarResource"])
            ->will($this->onConsecutiveCalls($hitted, $notHitted));
        
        $storage = new CacheItemPoolTokenResourceStorage($pool);
        
        $this->assertSame("FooBar", $storage->get("FooResource"));
        $this->assertNull($storage->get("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheItemPoolTokenResourceStorage::get()
     */
    public function testAdd(): void
    {
        $standardItem = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $standardItem->expects($this->once())->method("set")->with("FooBar")->will($this->returnSelf());
        $standardItem->expects($this->once())->method("expiresAfter")->with(20)->will($this->returnSelf());
        
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->once())->method("getItem")->with(CacheItemPoolTokenResourceStorage::PREFIX."FooResource")->will($this->returnValue($standardItem));
        $pool->expects($this->once())->method("save")->with($standardItem)->will($this->returnValue(true));
        
        $storage = new CacheItemPoolTokenResourceStorage($pool);
        
        $this->assertTrue($storage->add("FooResource", "FooBar", 20));
        
        if(\interface_exists("Cache\TagInterop\TaggableCacheItemPoolInterface")) {
            $taggableItem = $this->getMockBuilder(TaggableCacheItemInterface::class)->getMock();
            $taggableItem->expects($this->once())->method("set")->with("FooBar")->will($this->returnSelf());
            $taggableItem->expects($this->once())->method("expiresAfter")->with(20)->will($this->returnSelf());
            $taggableItem->expects($this->once())->method("setTags")->with([CacheItemPoolTokenResourceStorage::TAG]);
            
            $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
            $pool->expects($this->once())->method("getItem")->with(CacheItemPoolTokenResourceStorage::PREFIX."FooResource")->will($this->returnValue($taggableItem));
            $pool->expects($this->once())->method("save")->with($taggableItem)->will($this->returnValue(true));
            
            $storage = new CacheItemPoolTokenResourceStorage($pool);
            
            $this->assertTrue($storage->add("FooResource", "FooBar", 20));
        }
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheItemPoolTokenResourceStorage::get()
     */
    public function testRemove(): void
    {
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool
            ->expects($this->exactly(2))
            ->method("deleteItem")
            ->withConsecutive([CacheItemPoolTokenResourceStorage::PREFIX."FooResource"], [CacheItemPoolTokenResourceStorage::PREFIX."BarResource"])
            ->will($this->onConsecutiveCalls(true, false));
        
        $storage = new CacheItemPoolTokenResourceStorage($pool);
        
        $this->assertTrue($storage->remove("FooResource"));
        $this->assertFalse($storage->remove("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheItemPoolTokenResourceStorage::get()
     */
    public function testClear(): void
    {
        $this->assertNull( (new CacheItemPoolTokenResourceStorage($this->getMockBuilder(CacheItemPoolInterface::class)->getMock()))->clear() );
        
        if(\interface_exists("Cache\TagInterop\TaggableCacheItemPoolInterface")) {
            $pool = $this->getMockBuilder(TaggableCacheItemPoolInterface::class)->getMock();
            $pool->expects($this->once())->method("invalidateTag")->with(CacheItemPoolTokenResourceStorage::TAG);
            
            $storage = new CacheItemPoolTokenResourceStorage($pool);
            
            $this->assertNull($storage->clear());
        }
    }
    
}
