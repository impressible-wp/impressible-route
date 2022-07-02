<?php

namespace Impressible\ImpressibleRouteTest;

use Impressible\ImpressibleRoute\LazyLoadObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Impressible\ImpressibleRoute\LazyLoadObject
 */
final class LazyLoadObjectTest extends TestCase
{
    public function testCallMethod()
    {
        $mock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['doSomething'])
            ->getMock();
        $mock->expects($this->exactly(1))
            ->method('doSomething')
            ->with($this->equalTo('foo'), $this->equalTo('bar'));

        // Test promise with the mock.
        $promise = new LazyLoadObject(fn() => $mock);
        $promise->doSomething('foo', 'bar');
    }

    public function testFromContainer()
    {
        // Create a mock object to load.
        $mock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['doSomething'])
            ->getMock();
        $mock->expects($this->exactly(1))
            ->method('doSomething')
            ->with($this->equalTo('foo'), $this->equalTo('bar'));

        // Creat stub of a container.
        $container = $this->createStub(ContainerInterface::class);

        /**
         * @var \PHPUnit\Framework\MockObject\Stub $stub
         */
        $stub = $container;
        $stub->method('get')->willReturn($mock);

        /**
         * @var LazyLoadObject $promise
         */
        $promise = LazyLoadObject::fromContainer($container, 'Foo');
        $promise->doSomething('foo', 'bar');
    }
}