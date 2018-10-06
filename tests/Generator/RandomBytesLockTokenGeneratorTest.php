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
 
namespace NessTest\Component\Lockery\Generator;

use NessTest\Component\Lockery\LockeryTestCase;
use Ness\Component\Lockery\Generator\RandomBytesLockTokenGenerator;
use Ness\Component\Lockery\LockToken;
use Ness\Component\Lockery\LockableResourceInterface;

/**
 * RandomBytesLockTokenGenerator testcase
 * 
 * @see \Ness\Component\Lockery\Generator\RandomBytesLockTokenGenerator
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RandomBytesLockTokenGeneratorTest extends LockeryTestCase
{
    
    /**
     * @see \Ness\Component\Lockery\Generator\RandomBytesLockTokenGenerator::generate()
     */
    public function testGenerate(): void
    {
        $resource = $this->getMockBuilder(LockableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getLockableResourceName")->will($this->returnValue("FooResource"));
        
        $generator = new RandomBytesLockTokenGenerator();
        
        $token = $generator->generate($resource);
        $this->assertInstanceOf(LockToken::class, $token);
        
        $token = \json_encode($token);
        
        $this->assertSame(32, \strlen(\json_decode($token)[1]));
        $this->assertSame("FooResource", \json_decode($token)[0]);
        
        $generator = new RandomBytesLockTokenGenerator(64);
        
        $token = $generator->generate($resource);
        
        $token = \json_encode($token);
        $this->assertSame(64, \strlen(\json_decode($token)[1]));
        $this->assertSame("FooResource", \json_decode($token)[0]);
    }
    
}
