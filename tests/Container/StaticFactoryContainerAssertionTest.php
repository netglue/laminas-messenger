<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Netglue\PsrContainer\Messenger\Exception\BadMethodCall;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StaticFactoryContainerAssertionTest extends TestCase
{
    /** @var object */
    private $subject;

    protected function setUp() : void
    {
        parent::setUp();
        $this->subject = new class() {
            use StaticFactoryContainerAssertion;

            /** @param mixed[] $arguments */
            public function callStatic(string $method, array $arguments) : ContainerInterface
            {
                return self::assertContainer($method, $arguments);
            }
        };
    }

    public function testExceptionThrownWhenAContainerIsNotTheFirstArgument() : void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('The first argument to foo must be an instance of');
        $this->subject->callStatic('foo', []);
    }

    public function testTheContainerInTheFirstArgumentWillBeReturned() : void
    {
        $container = $this->createMock(ContainerInterface::class);
        self::assertSame($container, $this->subject->callStatic('foo', [$container]));
    }
}
