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
 
namespace NessTest\Component\Lockey\Iterator;

use NessTest\Component\Lockey\LockeyTestCase;
use NessTest\Component\Lockey\Fixtures\Iterator\SimpleHierarchy\MozResource as SimpleMozResource;
use NessTest\Component\Lockey\Fixtures\Iterator\SimpleHierarchy\BarResource as SimpleBarResource;
use NessTest\Component\Lockey\Fixtures\Iterator\SimpleHierarchy\FooResource as SimpleFooResource;
use Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\KekResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\BarBarResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\FooFooResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\FooBarResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\MozFooResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\MozResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\BarResource;
use NessTest\Component\Lockey\Fixtures\Iterator\MultipleHierarchy\FooResource;
use Ness\Component\Lockey\Exception\InvalidArgumentException;
use NessTest\Component\Lockey\Fixtures\Iterator\Invalid\InvalidResource;

/**
 * HierarchyRecursiveIterator testcase
 * 
 * @see \Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class HierarchyRecursiveIteratorTest extends LockeyTestCase
{
    
    /**
     * @see \Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator
     */
    public function testSimpleResourceIteration(): void
    {
        $resource = new SimpleMozResource(new SimpleBarResource(new SimpleFooResource()));
        
        $resources = [];
        foreach (new \RecursiveIteratorIterator(new HierarchyRecursiveIterator($resource), \RecursiveIteratorIterator::SELF_FIRST) as $name => $resource) {
            $resources[$name] = $resource;
        }
        
        $this->assertCount(2, $resources);
        $this->assertInstanceOf(SimpleBarResource::class, $resources["BarResource"]);
        $this->assertInstanceOf(SimpleFooResource::class, $resources["FooResource"]);
    }
    
    /**
     * @see \Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator
     */
    public function testComplexeResourceIteration(): void
    {
        $resource = new KekResource(
            new MozResource(
                new BarBarResource(
                    new BarResource(
                        new FooResource(), 
                        new FooFooResource()), 
                    new FooBarResource())), 
            new MozFooResource());
        $resources = [];
        
        foreach (new \RecursiveIteratorIterator(new HierarchyRecursiveIterator($resource), \RecursiveIteratorIterator::SELF_FIRST) as $name => $resource) {
            $resources[$name] = $resource;
        }
        
        $this->assertCount(7, $resources);
        $this->assertInstanceOf(MozResource::class, $resources["MozResource"]);
        $this->assertInstanceOf(BarBarResource::class, $resources["BarBarResource"]);
        $this->assertInstanceOf(BarResource::class, $resources["BarResource"]);
        $this->assertInstanceOf(FooResource::class, $resources["FooResource"]);
        $this->assertInstanceOf(FooFooResource::class, $resources["FooFooResource"]);
        $this->assertInstanceOf(FooBarResource::class, $resources["FooBarResource"]);
        $this->assertInstanceOf(MozFooResource::class, $resources["MozFooResource"]);
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Lockey\Iterator\HierarchyRecursiveIterator::current()
     */
    public function testExceptionWhenInvalidClassIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource declared into hierarchy of resource InvalidResource MUST be an instance of LockableResourceInterface");
        
        $resource = new InvalidResource();
        
        foreach (new \RecursiveIteratorIterator(new HierarchyRecursiveIterator($resource), \RecursiveIteratorIterator::SELF_FIRST) as $name => $resource) {
            
        }
    }
    
}
