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
 
namespace NessTest\Component\Lockey\Generator;

use NessTest\Component\Lockey\LockeyTestCase;
use Ness\Component\Lockey\Generator\RandomBytesLockTokenGenerator;
use Ness\Component\Lockey\LockToken;
use Ness\Component\Lockey\LockableResourceInterface;

/**
 * RandomBytesLockTokenGenerator testcase
 * 
 * @see \Ness\Component\Lockery\Generator\RandomBytesLockTokenGenerator
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RandomBytesLockTokenGeneratorTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Generator\RandomBytesLockTokenGenerator::generate()
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
