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
 
namespace NessTest\Component\Lockey\Storage\Identity;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\User\UserInterface;
use Ness\Component\Lockey\Storage\Identity\UserAttributeTokenIdentityStorage;

/**
 * UserAttributeTokenIdentityStorage testcase
 * 
 * @see \Ness\Component\Lockery\Storage\Identity\UserAttributeTokenIdentityStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UserAttributeTokenIdentityStorageTest extends LockeyTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists("Ness\Component\User\UserInterface"))
            self::markTestSkipped("Ness/User component not found. Test skipped");
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Identity\UserAttributeTokenIdentityStorage::add()
     */
    public function testAdd(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(2))
            ->method("getAttribute")
            ->withConsecutive([UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls([], ["FooResource" => "FooToken"]));
        $user
            ->expects($this->exactly(2))
            ->method("addAttribute")
            ->withConsecutive(
                [UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER, ["FooResource" => "FooToken"]],
                [UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER, ["FooResource" => "FooToken", "BarResource" => "BarToken"]]);
        
        $store = new UserAttributeTokenIdentityStorage();
        $store->setUser($user);
        
        $store->add("FooResource", "FooToken");
        $store->add("BarResource", "BarToken");
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Identity\UserAttributeTokenIdentityStorage::get()
     */
    public function testGet(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(2))
            ->method("getAttribute")
            ->withConsecutive([UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls([], ["FooResource" => "FooToken"]));
        
        $store = new UserAttributeTokenIdentityStorage();
        $store->setUser($user);
        
        $this->assertNull($store->get("FooResource"));
        $this->assertSame("FooToken", $store->get("FooResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Identity\UserAttributeTokenIdentityStorage::remove()
     */
    public function testRemove(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(
                ["FooResource" => "FooToken"], 
                ["FooResource" => "FooToken", "BarResource" => "BarToken"],
                ["FooResource" => "FooToken", "BarResource" => "BarToken"]));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER, []],
                [UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER, ["BarResource" => "BarToken"]],
                [UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER, ["FooResource" => "FooToken", "BarResource" => "BarToken"]]
            );
        
        $store = new UserAttributeTokenIdentityStorage();
        $store->setUser($user);
        
        $this->assertTrue($store->remove("FooResource"));
        $this->assertTrue($store->remove("FooResource"));
        $this->assertFalse($store->remove("MozResource"));
    }
    
    /**
     * @see \Ness\Component\Lockey\Storage\Identity\UserAttributeTokenIdentityStorage::clear()
     */
    public function testClear(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("deleteAttribute")->with(UserAttributeTokenIdentityStorage::ATTRIBUTE_IDENTIFIER);
        
        $store = new UserAttributeTokenIdentityStorage();
        $store->setUser($user);
        
        $this->assertNull($store->clear());
    }
    
}
