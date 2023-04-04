<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\Util;
use Netglue\PsrContainer\Messenger\Exception\BadMethodCall;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StaticFactoryContainerAssertionTest extends TestCase
{
    public function testExceptionThrownWhenAContainerIsNotTheFirstArgument(): void
    {
        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage('The first argument to foo must be an instance of');
        Util::assertStaticFactoryContainer('foo', []);
    }

    public function testTheContainerInTheFirstArgumentWillBeReturned(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        self::assertSame($container, Util::assertStaticFactoryContainer('foo', [$container]));
    }
}
