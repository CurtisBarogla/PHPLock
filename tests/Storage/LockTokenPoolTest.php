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
 
namespace NessTest\Component\Lockey\Storage;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Storage\LockTokenPool;
use Ness\Component\Lockey\Storage\Adapter\LockTokenStoreAdapterInterface;
use Ness\Component\Lockey\Normalizer\ResourceNormalizerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\Exception\TokenPoolTransactionErrorException;

/**
 * LockTokenPool testcase
 * 
 * @see \Ness\Component\Lockey\Storage\LockTokenPool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockTokenPoolTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getToken()
     */
    public function testGetTokenWithNoTokenFoundWithNoHierarchy(): void
    {       
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->once())->method("getLockableHierarchy")->will($this->returnValue(null));
        $action = function(MockObject $adapter, MockObject $normalizer): void {
            $normalizer->expects($this->once())->method("normalize")->with("FooResource")->will($this->returnValue("::FooResource::"));
            $adapter->expects($this->once())->method("get")->with("ness_lock_token_pool::FooResource::")->will($this->returnValue(null));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertNull($pool->getToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getToken()
     */
    public function testGetTokenWithNoTokenFoundWithNoTokenFoundIntoHierarchy(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"), 
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(3))
                ->method("get")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"],
                    ["ness_lock_token_pool::MozResource::"]
                )
                ->will($this->onConsecutiveCalls(null, null, null));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertNull($pool->getToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getToken()
     */
    public function testGetTokenWithTokenFoundIntoMainResource(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token = LockToken::createFromJson(\json_encode($token));
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->never())->method("getLockableHierarchy");
        $action = function(MockObject $adapter, MockObject $normalizer) use ($token): void {
            $normalizer->expects($this->once())->method("normalize")->with("FooResource")->will($this->returnValue("::FooResource::"));
            $adapter->expects($this->once())->method("get")->with("ness_lock_token_pool::FooResource::")->will($this->returnValue(\json_encode($token)));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertEquals($token, $pool->getToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::getToken()
     */
    public function testGetTokenWithNoTokenFoundIntoMainResourceButFoundIntoHierarchy(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        $token = LockToken::createFromJson(\json_encode($token));
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"), 
            self::initializeResource("MozResource"), 
            self::initializeResource("PozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($token): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(3))
                ->method("get")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"],
                    ["ness_lock_token_pool::MozResource::"]
                )
                ->will($this->onConsecutiveCalls(null, null, \json_encode($token)));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertEquals($token, $pool->getToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::saveToken()
     */
    public function testSaveTokenWithNoHierarchy(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity(new \DateInterval("PT20S"));
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->exactly(2))->method("getLockableHierarchy")->will($this->returnValue(null));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($token): void {
            $normalizer->expects($this->exactly(2))->method("normalize")->with("FooResource")->will($this->returnValue("::FooResource::"));
            $adapter->expects($this->exactly(2))->method("add")->with("ness_lock_token_pool::FooResource::", \json_encode($token), 20)->will($this->onConsecutiveCalls(true, false));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->saveToken($token, $resource));
        $this->assertFalse($pool->saveToken($token, $resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::saveToken()
     */
    public function testSaveTokenWithHierarchyWithNoFail(): void
    {
        $interval = new \DateInterval("PT20S");
        $tokenFoo = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $tokenBar = new LockToken("BarResource", LockToken::EXCLUSIVE);
        $tokenMoz = new LockToken("MozResource", LockToken::EXCLUSIVE);
        foreach ([$tokenFoo, $tokenBar, $tokenMoz] as $token)
            $token->setValidity($interval);
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"), 
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($tokenFoo, $tokenBar, $tokenMoz): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(3))
                ->method("add")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::", \json_encode($tokenFoo), 20],
                    ["ness_lock_token_pool::BarResource::", \json_encode($tokenBar), 20],
                    ["ness_lock_token_pool::MozResource::", \json_encode($tokenMoz), 20]
                )
                ->will($this->onConsecutiveCalls(true, true, true));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->saveToken($tokenFoo, $resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::saveToken()
     */
    public function testSaveTokenWithHierarchyWithFailWithReverseSuccessfully(): void
    {
        $interval = new \DateInterval("PT20S");
        $tokenFoo = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $tokenBar = new LockToken("BarResource", LockToken::EXCLUSIVE);
        $tokenMoz = new LockToken("MozResource", LockToken::EXCLUSIVE);
        foreach ([$tokenFoo, $tokenBar, $tokenMoz] as $token)
            $token->setValidity($interval);
            
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"), 
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($tokenFoo, $tokenBar, $tokenMoz): void {
            $previousToken = new LockToken("BarResource", LockToken::EXCLUSIVE);
            $previousToken->setValidity(new \DateInterval("PT10S"));
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )
                ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(4))
                ->method("add")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::", \json_encode($tokenFoo), 20],
                    ["ness_lock_token_pool::BarResource::", \json_encode($tokenBar), 20],
                    ["ness_lock_token_pool::MozResource::", \json_encode($tokenMoz), 20],
                    ["ness_lock_token_pool::BarResource::", \json_encode($previousToken), 10]
                )
                ->will($this->onConsecutiveCalls(true, true, false, true));
            $adapter
                ->expects($this->once())
                ->method("remove")
                ->with("ness_lock_token_pool::FooResource::")
                ->will($this->returnValue(true));
            $adapter
                ->expects($this->exactly(2))
                ->method("get")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"]
                )
                ->will($this->onConsecutiveCalls(null, \json_encode($previousToken)));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertFalse($pool->saveToken($tokenFoo, $resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::saveToken()
     */
    public function testSaveTokenWithHierarchyWithFailWithErrorRollback(): void
    {
        $interval = new \DateInterval("PT20S");
        $tokenFoo = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $tokenBar = new LockToken("BarResource", LockToken::EXCLUSIVE);
        $tokenMoz = new LockToken("MozResource", LockToken::EXCLUSIVE);
        foreach ([$tokenFoo, $tokenBar, $tokenMoz] as $token)
            $token->setValidity($interval);
            
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"),
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($tokenFoo, $tokenBar, $tokenMoz): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )
                ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(3))
                ->method("add")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::", \json_encode($tokenFoo), 20],
                    ["ness_lock_token_pool::BarResource::", \json_encode($tokenBar), 20],
                    ["ness_lock_token_pool::MozResource::", \json_encode($tokenMoz), 20]
                )
                ->will($this->onConsecutiveCalls(true, true, false));
            $adapter
                ->expects($this->exactly(2))
                ->method("remove")
                ->withConsecutive(["ness_lock_token_pool::FooResource::"], ["ness_lock_token_pool::BarResource::"])
                ->will($this->onConsecutiveCalls(true, false));
        };
            
        $pool = $this->getPool($action);
        
        try {
            $pool->saveToken($tokenFoo, $resource);            
        } catch (TokenPoolTransactionErrorException $e) {
            $this->assertSame(["ness_lock_token_pool::BarResource::"], $e->getKeys());
        }
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::deleteToken()
     */
    public function testDeleteTokenWithNoTokenFoundWithNoHierarchy(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->never())->method("getLockableHierarchy")->will($this->returnValue(null));
        
        $action = function(MockObject $adapter, MockObject $normalizer): void {
            $normalizer->expects($this->once())->method("normalize")->with("FooResource")->will($this->returnValue("::FooResource::"));
            $adapter->expects($this->once())->method("get")->with("ness_lock_token_pool::FooResource::")->will($this->returnValue(null));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertFalse($pool->deleteToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::deleteToken()
     */
    public function testDeleteTokenWithTokenFoundWithNoHierarchy(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->exactly(2))->method("getLockableHierarchy")->will($this->returnValue(null));
        
        $action = function(MockObject $adapter, MockObject $normalizer): void {
            $normalizer->expects($this->exactly(2))->method("normalize")->with("FooResource")->will($this->returnValue("::FooResource::"));
            $adapter->expects($this->exactly(2))->method("get")->with("ness_lock_token_pool::FooResource::")->will($this->returnValue("FooToken"));
            $adapter->expects($this->exactly(2))->method("remove")->with("ness_lock_token_pool::FooResource::")->will($this->onConsecutiveCalls(true, false));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->deleteToken($resource));
        $this->assertFalse($pool->deleteToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::deleteToken()
     */
    public function testDeleteTokenWithTokenWithHierarchyWithNoError(): void
    {
        $interval = new \DateInterval("PT20S");
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setValidity($interval);
        $json = \json_encode($token);
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"),
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($json): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )
                ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->once())
                ->method("get")
                ->with("ness_lock_token_pool::FooResource::")
                ->will($this->returnValue($json));
            $adapter
                ->expects($this->exactly(3))
                ->method("remove")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"],
                    ["ness_lock_token_pool::MozResource::"]
                )
                ->will($this->onConsecutiveCalls(true, true, true));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertTrue($pool->deleteToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::deleteToken()
     */
    public function testDeleteTokenWithTokenWithHierarchyWithErrorNoErrorRollback(): void
    {
        $json = [];
        $interval = new \DateInterval("PT20S");
        $tokenFoo = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $tokenBar = new LockToken("BarResource", LockToken::EXCLUSIVE);
        $tokenMoz = new LockToken("MozResource", LockToken::EXCLUSIVE);
        
        foreach ([$tokenFoo, $tokenBar, $tokenMoz] as $token) {
            $token->setValidity($interval);
            $json[$token->getResource()] = \json_encode($token);            
        }
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"),
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($json): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]    
                )
                ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(2))
                ->method("get")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"]
                )
                ->will($this->onConsecutiveCalls($json["FooResource"], $json["BarResource"]));
            $adapter
                ->expects($this->exactly(3))
                ->method("remove")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"],
                    ["ness_lock_token_pool::MozResource::"]
                )->will($this->onConsecutiveCalls(true, true, false));
            $adapter
                ->expects($this->exactly(2))
                ->method("add")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::", $json["FooResource"], 20],
                    ["ness_lock_token_pool::BarResource::", $json["BarResource"], 20]
                )
                ->will($this->onConsecutiveCalls(true, true));
        };
        
        $pool = $this->getPool($action);
        
        $this->assertFalse($pool->deleteToken($resource));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\LockTokenPool::deleteToken()
     */
    public function testDeleteTokenWithTokenWithHierarchyWithErrorWithErrorRollback(): void
    {
        $json = [];
        $interval = new \DateInterval("PT20S");
        $tokenFoo = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $tokenBar = new LockToken("BarResource", LockToken::EXCLUSIVE);
        $tokenMoz = new LockToken("MozResource", LockToken::EXCLUSIVE);
        
        foreach ([$tokenFoo, $tokenBar, $tokenMoz] as $token) {
            $token->setValidity($interval);
            $json[$token->getResource()] = \json_encode($token);
        }
        
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getLockableName")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getLockableHierarchy")->will($this->returnValue([
            self::initializeResource("BarResource"),
            self::initializeResource("MozResource")]));
        
        $action = function(MockObject $adapter, MockObject $normalizer) use ($json): void {
            $normalizer
                ->expects($this->exactly(3))
                ->method("normalize")
                ->withConsecutive(
                    ["FooResource"],
                    ["BarResource"],
                    ["MozResource"]
                )
                ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
            $adapter
                ->expects($this->exactly(2))
                ->method("get")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"]
                )
                ->will($this->onConsecutiveCalls($json["FooResource"], $json["BarResource"]));
            $adapter
                ->expects($this->exactly(3))
                ->method("remove")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::"],
                    ["ness_lock_token_pool::BarResource::"],
                    ["ness_lock_token_pool::MozResource::"]
                )
                ->will($this->onConsecutiveCalls(true, true, false));
            $adapter
                ->expects($this->exactly(2))
                ->method("add")
                ->withConsecutive(
                    ["ness_lock_token_pool::FooResource::", $json["FooResource"], 20],
                    ["ness_lock_token_pool::BarResource::", $json["BarResource"], 20]
                )
                ->will($this->onConsecutiveCalls(true, false));
        };
        
        $pool = $this->getPool($action);
        
        try {
            $this->assertFalse($pool->deleteToken($resource));            
        } catch (TokenPoolTransactionErrorException $e) {
            $this->assertSame(["ness_lock_token_pool::BarResource::"], $e->getKeys());
        }
    }
    
    /**
     * Get an initialized lock token pool
     * 
     * @param \Closure|null $action
     *   Action performed on the adapter and the normalizer
     * 
     * @return LockTokenPool
     *   Lock token pool initialized
     */
    private function getPool(?\Closure $action): LockTokenPool
    {
        $adapter = $this->getMockBuilder(LockTokenStoreAdapterInterface::class)->getMock();
        $normalizer = $this->getMockBuilder(ResourceNormalizerInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $adapter, $normalizer);
        
        return new LockTokenPool($adapter, $normalizer);
    }
    
    /**
     * Initialize a new simple anonymous class wrapping an implementation of LockableResourceInterface
     * 
     * @param string $name
     *   Resource name
     * 
     * @return LockableResourceInterface
     *   Lockable resource as anonymous class
     */
    private static function initializeResource(string $name): LockableResourceInterface
    {
        return new class($name) implements LockableResourceInterface {
            
            /**
             * Resource name
             * 
             * @var string
             */
            private $name;
            
            /**
             * Initialize anonymous resource
             * 
             * @param string $name
             *   Resource name
             */
            public function __construct(string $name)
            {
                $this->name = $name;
            }
            
            /**
             * {@inheritDoc}
             * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableName()
             */
            public function getLockableName(): string
            {
                return $this->name;
            }
            
            /**
             * {@inheritDoc}
             * @see \Ness\Component\Lockey\LockableResourceInterface::getLockableHierarchy()
             */
            public function getLockableHierarchy(): ?array
            {
                return null;
            }
        };
    }
    
}
