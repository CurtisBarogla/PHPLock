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

namespace Ness\Component\Lockey\Storage\Adapter {
    global $fails;
    
    /**
     * Set the apcu store to the given state
     *
     * @param bool $result
     *   Next result all further calls to apcu_store until reset
     */
    function init(bool $result): void
    {
        global $fails;
        $fails = $result;
    }
    
    /**
     * {@inheritdoc}
     */
    function apcu_store($key, $var, $ttl)
    {
        global $fails;
        if($fails)
            return false;
        
        return \apcu_store($key, $var, $ttl);
    }
    
    /**
     * Reset apcu state
     */
    function reset(): void
    {
        global $fails;
        $fails = false;
    }
};

namespace NessTest\Component\Lockey\Storage\Adapter {
    
    use NessTest\Component\Lockey\LockeyTestCase;
    use Ness\Component\Lockey\Storage\Adapter\ApcuLockTokenStoreAdapter;
    use function Ness\Component\Lockey\Storage\Adapter\init;
    use function Ness\Component\Lockey\Storage\Adapter\reset;
                                                    
    /**
     * ApcuLockTokenStoreAdapter testcase
     * 
     * @see \Ness\Component\Lockey\Storage\Adapter\ApcuLockTokenStoreAdapter
     * 
     * @author CurtisBarogla <curtis_barogla@outlook.fr>
     *
     */
    class ApcuLockTokenStoreAdapterTest extends LockeyTestCase
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
         * @see \PHPUnit\Framework\TestCase::tearDown()
         */
        protected function tearDown(): void
        {
            \apcu_clear_cache();
            reset();
        }
        
        /**
         * @see \Ness\Component\Lockey\Storage\Adapter\ApcuLockTokenStoreAdapter::get()
         */
        public function testGet(): void
        {
            $adapter = new ApcuLockTokenStoreAdapter();
            
            $this->assertNull($adapter->get("FooResource"));
            
            \apcu_store("FooResource", "FooResourceToken");
            
            $this->assertSame("FooResourceToken", $adapter->get("FooResource"));
        }
        
        /**
         * @see \Ness\Component\Lockey\Storage\Adapter\ApcuLockTokenStoreAdapter::add()
         */
        public function testAdd(): void
        {
            init(false);
            $adapter = new ApcuLockTokenStoreAdapter();
            
            $this->assertTrue($adapter->add("FooResource", "FooResourceToken", 1));
            $this->assertSame("FooResourceToken", \apcu_fetch("FooResource"));
            
            reset();
            init(true);
            $this->assertFalse($adapter->add("FooResource", "FooResourceToken", 2));
        }
        
        /**
         * @see \Ness\Component\Lockey\Storage\Adapter\ApcuLockTokenStoreAdapter::remove()
         */
        public function testRemove(): void
        {
            $adapter = new ApcuLockTokenStoreAdapter();
            
            $this->assertFalse($adapter->remove("FooResource"));
            \apcu_store("FooResource", "FooResourceToken");
            $this->assertTrue($adapter->remove("FooResource"));
        }
        
    }
    
}
