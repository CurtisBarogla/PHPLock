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
use Ness\Component\Lockey\Storage\Resource\ApcuTokenResourceStorage;

/**
 * ApcuTokenResourceStorage testcase
 * 
 * @see \Ness\Component\Lockery\Storage\Resource\ApcuTokenResourceStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuTokenResourceStorageTest extends LockeyTestCase
{

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\extension_loaded("apcu") || \ini_get("apc.enable_cli") === "0")
            self::markTestSkipped("Apcu extension not loaded or not enabled in cli mode. Test skipped");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void 
    {
        \apcu_clear_cache();
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\ApcuTokenResourceStorage::get()
     */
    public function testGet(): void
    {
        \apcu_store(ApcuTokenResourceStorage::KEY_PREFIX."FooResource", "FooBar");
        
        $store = new ApcuTokenResourceStorage();   
        
        $this->assertSame("FooBar", $store->get("FooResource"));
        $this->assertNull($store->get("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\ApcuTokenResourceStorage::add()
     */
    public function testAdd(): void
    {
        $store = new ApcuTokenResourceStorage();
        
        $this->assertTrue($store->add("FooResource", "FooBar", 1));
        $this->assertSame("FooBar", $store->get("FooResource"));
        
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\ApcuTokenResourceStorage::remove()
     */
    public function testRemove(): void
    {
        \apcu_store(ApcuTokenResourceStorage::KEY_PREFIX."FooResource", "FooBar");
        
        $store = new ApcuTokenResourceStorage();
        
        $this->assertTrue($store->remove("FooResource"));
        $this->assertFalse($store->remove("FooResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Resource\ApcuTokenResourceStorage::clear()
     */
    public function testClear(): void
    {
        \apcu_store(ApcuTokenResourceStorage::KEY_PREFIX."FooResource", "FooBar");
        \apcu_store("foo", "bar");
        
        $store = new ApcuTokenResourceStorage();
        
        $this->assertNull($store->clear());
        $this->assertNull($store->get("FooResource"));
        $this->assertSame("bar", \apcu_fetch("foo"));
    }
    
}
