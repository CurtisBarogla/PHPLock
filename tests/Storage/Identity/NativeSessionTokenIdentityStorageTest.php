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

namespace Ness\Component\Lockery\Storage\Identity {
        
    $started = null;
    
    /**
     * Initialize globals
     *
     * @var int $sessionStatus
     *   Current session status. Const defined into php
     */
    function initGlobals(int $sessionStatus): void
    {
        global $started;
        
        $started = $sessionStatus;
    }
    
    /**
     * Mock session_status
     *
     * @return int
     *   Session status defined by call to initGlobals
     */
    function session_status()
    {
        global $started;
        
        return $started;
    }
};

namespace NessTest\Component\Lockery\Storage\Identity {

    use NessTest\Component\Lockery\LockeryTestCase;
    use Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage;
use function Ness\Component\Lockery\Storage\Identity\initGlobals;
                                    
    /**
     * NativeSessionTokenIdentityStorage testcase
     * 
     * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage
     * 
     * @author CurtisBarogla <curtis_barogla@outlook.fr>
     *
     */
    class NativeSessionTokenIdentityStoragetTest extends LockeryTestCase
    {
        
        /**
         * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage::get()
         */
        public function testGet(): void
        {
            initGlobals(PHP_SESSION_ACTIVE);
            $store = new NativeSessionTokenIdentityStorage();
            
            $this->assertNull($store->get("FooResource"));
            $store->add("FooResource", "FooToken");
            
            $this->assertSame("FooToken", $store->get("FooResource"));
        }
        
        /**
         * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage::add()
         */
        public function testAdd(): void
        {
            initGlobals(PHP_SESSION_ACTIVE);
            $store = new NativeSessionTokenIdentityStorage();

            $this->assertTrue($store->add("FooResource", "FooToken"));
            $this->assertSame("FooToken", $store->get("FooResource"));
        }
        
        /**
         * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage::remove()
         */
        public function testRemove(): void
        {
            initGlobals(PHP_SESSION_ACTIVE);
            $store = new NativeSessionTokenIdentityStorage();

            $store->add("FooResource", "FooToken");
            
            $this->assertSame("FooToken", $store->get("FooResource"));
            $this->assertTrue($store->remove("FooResource"));
            $this->assertNull($store->get("FooResource"));
            
            $this->assertFalse($store->remove("FooResource"));
        }
        
        /**
         * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage::clear()
         */
        public function testClear(): void
        {
            initGlobals(PHP_SESSION_ACTIVE);
            $store = new NativeSessionTokenIdentityStorage();

            $store->add("FooResource", "FooToken");
            $store->add("BarResource", "BarToken");
            
            $this->assertSame("FooToken", $store->get("FooResource"));
            $this->assertSame("BarToken", $store->get("BarResource"));
            
            $this->assertNull($store->clear());
            
            $this->assertNull($store->get("FooResource"));
            $this->assertNull($store->get("BarResource"));
        }
        
        /**_____EXCEPTIONS_____**/
        
        /**
         * @see \Ness\Component\Lockery\Storage\Identity\NativeSessionTokenIdentityStorage::__construct()
         */
        public function testException__constructWhenSessionIsNotActive(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage("Session MUST be enable to use the NativeSessionTokenIdentityStorage");
            
            initGlobals(PHP_SESSION_DISABLED);
            
            $store = new NativeSessionTokenIdentityStorage();
        }
        
        /**
         * Get the storage initialized with a mocked session setted into it
         * 
         * @param NativeSessionTokenIdentityStorage $storage
         *   Storage tested to initialize
         */
        private function getStorage(NativeSessionTokenIdentityStorage $storage): void
        {
            $reflection = new \ReflectionClass($storage);
            $property = $reflection->getProperty("session");
            $property->setAccessible(true);
            $property->setValue($storage, []);
        }
        
    }
    
}
