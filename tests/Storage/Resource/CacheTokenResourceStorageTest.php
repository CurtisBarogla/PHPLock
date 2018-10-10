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
use Psr\SimpleCache\CacheInterface;
use Ness\Component\Lockey\Storage\Resource\CacheTokenResourceStorage;

/**
 * CacheTokenResourceStorage testcase
 * 
 * @see \Ness\Component\Lockery\Storage\Resource\CacheTokenResourceStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTokenResourceStorageTest extends LockeyTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 not found. Test skipped");
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheTokenResourceStorage::get()
     */
    public function testGet(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache
            ->expects($this->exactly(2))
            ->method("get")
            ->withConsecutive([CacheTokenResourceStorage::PREFIX."FooResource"], [CacheTokenResourceStorage::PREFIX."BarResource"])
            ->will($this->onConsecutiveCalls("FooBar", null));
        
        $storage = new CacheTokenResourceStorage($cache);
        
        $this->assertSame("FooBar", $storage->get("FooResource"));
        $this->assertNull($storage->get("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheTokenResourceStorage::add()
     */
    public function testAdd(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("set")->with(CacheTokenResourceStorage::PREFIX."FooResource", "FooBar", 20)->will($this->returnValue(true));
        
        $storage = new CacheTokenResourceStorage($cache);
        
        $this->assertTrue($storage->add("FooResource", "FooBar", 20));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheTokenResourceStorage::remove()
     */
    public function testRemove(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache
            ->expects($this->exactly(2))
            ->method("delete")
            ->withConsecutive([CacheTokenResourceStorage::PREFIX."FooResource"], [CacheTokenResourceStorage::PREFIX."BarResource"])
            ->will($this->onConsecutiveCalls(true, false));
        
        $storage = new CacheTokenResourceStorage($cache);
        
        $this->assertTrue($storage->remove("FooResource"));
        $this->assertFalse($storage->remove("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\CacheTokenResourceStorage::clear()
     */
    public function testClear(): void
    {
        $this->assertNull((new CacheTokenResourceStorage($this->getMockBuilder(CacheInterface::class)->getMock()))->clear());
    }
    
}
