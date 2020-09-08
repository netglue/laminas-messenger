<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\Middleware\BusNameStampMiddlewareStaticFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

use function assert;

class BusNameStampMiddlewareStaticFactoryTest extends TestCase
{
    public function testCreatedMiddlewareHasCorrectIdentifier(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $middleware = BusNameStampMiddlewareStaticFactory::__callStatic('whatever', [$container]);
        $envelope = new Envelope(new stdClass());

        $stamped = $middleware->handle($envelope, new StackMiddleware());
        $stamp = $stamped->last(BusNameStamp::class);
        self::assertInstanceOf(BusNameStamp::class, $stamp);
        assert($stamp instanceof BusNameStamp);
        self::assertSame('whatever', $stamp->getBusName());
    }
}
