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
use Ness\Component\Lockey\Storage\Adapter\CacheLockTokenStoreAdapter;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * CacheLockTokenStoreAdapter testcase
 * 
 * @see \Ness\Component\Lockey\Storage\Adapter\CacheLockTokenStoreAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheLockTokenStoreAdapterTest extends LockeyTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 Cache not found. Test skipped");
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheLockTokenStoreAdapter::get()
     */
    public function testGet(): void
    {
        $action = function(MockObject $cache): void {
            $cache
                ->expects($this->exactly(2))
                ->method("get")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls(null, "BarResourceToken"));   
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertNull($adapter->get("FooResource"));
        $this->assertSame("BarResourceToken", $adapter->get("BarResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheLockTokenStoreAdapter::add()
     */
    public function testAdd(): void
    {
        $action = function(MockObject $cache): void {
            $cache
                ->expects($this->exactly(2))
                ->method("set")
                ->withConsecutive(["FooResource", "FooResourceToken", 20], ["BarResource", "BarResourceToken", 13])
                ->will($this->onConsecutiveCalls(true, false));
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertTrue($adapter->add("FooResource", "FooResourceToken", 20));
        $this->assertFalse($adapter->add("BarResource", "BarResourceToken", 13));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Adapter\CacheLockTokenStoreAdapter::remove()
     */
    public function testRemove(): void
    {
        $action = function(MockObject $cache): void {
            $cache
                ->expects($this->exactly(2))
                ->method("delete")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls(true, false));
        };
        
        $adapter = $this->getAdapter($action);
        
        $this->assertTrue($adapter->remove("FooResource"));
        $this->assertFalse($adapter->remove("BarResource"));
    }
    
    /**
     * Get an initialized CacheLockTokenStoreAdapter with a PSR-16 Cache mock setted
     * 
     * @param \Closure|null
     *   Action to perform on the PSR-16 Cache implementation
     *
     * @return CacheLockTokenStoreAdapter
     *   Initialized tested adapter
     */
    private function getAdapter(?\Closure $action): CacheLockTokenStoreAdapter
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $cache);
        
        return new CacheLockTokenStoreAdapter($cache);
    }
    
}
