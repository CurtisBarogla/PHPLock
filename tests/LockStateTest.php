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

use Ness\Component\Lockey\LockableResourceInterface;
use Ness\Component\User\UserInterface;
use Ness\Component\Lockey\LockState;
use Ness\Component\Lockey\LockToken;

/**
 * LockState testcase
 * 
 * @see \Ness\Component\Lockey\LockState
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LockStateTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\LockState::getResource()
     */
    public function testGetResource(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $state = new LockState($resource, null, $user);
        
        $this->assertSame($resource, $state->getResource());
    }
    
    public function testIsLocked(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $state = new LockState($resource, $token, $user);
        
        $this->assertTrue($state->isLocked());
        
        $state = new LockState($resource, null, $user);
        
        $this->assertFalse($state->isLocked());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockState::isAccessible()
     */
    public function testIsAccessible(): void
    {
        $users = [
            "FooUser"   => $this->getMockBuilder(UserInterface::class)->getMock(),
            "BarUser"   => $this->getMockBuilder(UserInterface::class)->getMock(),
            "MozUser"   =>  $this->getMockBuilder(UserInterface::class)->getMock()
        ];
        foreach ($users as $name => $user) {
            $user->expects($this->any())->method("getName")->will($this->returnValue($name));
        }
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $token->setMaster($users["FooUser"]);
        
        $state = new LockState($resource, $token, $users["FooUser"]);
        $this->assertTrue($state->isAccessible());
        $state = new LockState($resource, $token, $users["BarUser"]);
        $this->assertFalse($state->isAccessible());
        
        $token = new LockToken("FooResource", LockToken::SHARE);
        $token->setMaster($users["FooUser"]);
        $token->shareWith($users["BarUser"]);
        
        $state = new LockState($resource, $token, $users["FooUser"]);
        $this->assertTrue($state->isAccessible());
        $state = new LockState($resource, $token, $users["BarUser"]);
        $this->assertTrue($state->isAccessible());
        $state = new LockState($resource, $token, $users["MozUser"]);
        $this->assertFalse($state->isAccessible());
        
        $state = new LockState($resource, null, $users["FooUser"]);
        
        $this->assertTrue($state->isAccessible());
        
        $token = new LockToken("FooResource", LockToken::FULL);
        $token->setMaster($users["FooUser"]);
        $token->shareWith($users["BarUser"]);
        
        $state = new LockState($resource, $token, $users["FooUser"]);
        $this->assertTrue($state->isAccessible());
        $state = new LockState($resource, $token, $users["BarUser"]);
        $this->assertTrue($state->isAccessible());
        $state = new LockState($resource, $token, $users["MozUser"]);
        $this->assertFalse($state->isAccessible());
        
        $state = new LockState($resource, null, $users["FooUser"]);
        
        $this->assertTrue($state->isAccessible());
    }
    
    /**
     * @see \Ness\Component\Lockey\LockState::getToken()
     */
    public function testGetToken(): void
    {
        $token = new LockToken("FooResource", LockToken::EXCLUSIVE);
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $state = new LockState($resource, null, $user);
        
        $this->assertNull($state->getToken());
        
        $state = new LockState($resource, $token, $user);
        
        $this->assertSame($token, $state->getToken());
    }
    
}
