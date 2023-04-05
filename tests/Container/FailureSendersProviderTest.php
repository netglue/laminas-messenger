<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\FailureSendersProvider;
use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailureSendersProviderTest extends TestCase
{
    public function testThatTheProvidedServicesHaveTheExpectedValues(): void
    {
        $provider = new FailureSendersProvider(
            $this->createMock(ContainerInterface::class),
            [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        );

        self::assertEquals([
            'foo' => TransportInterface::class,
            'baz' => TransportInterface::class,
        ], $provider->getProvidedServices());
    }

    public function testThatHasReturnsTrueOnlyForConfiguredSenders(): void
    {
        $provider = new FailureSendersProvider(
            $this->createMock(ContainerInterface::class),
            [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        );

        self::assertTrue($provider->has('foo'));
        self::assertFalse($provider->has('bar'));
        self::assertTrue($provider->has('baz'));
        self::assertFalse($provider->has('bat'));
    }

    public function testThatGetThrowsAnExceptionForAnUnknownTransport(): void
    {
        $provider = new FailureSendersProvider(
            $this->createMock(ContainerInterface::class),
            [
                'foo' => 'bar',
                'baz' => 'bat',
            ],
        );

        $this->expectException(ServiceNotFound::class);
        $this->expectExceptionMessage('A transport with the name "[null]" is either not present in the DI container');
        $provider->get('goats');
    }

    public function testAnExceptionIsThrownWhenTheMappedTransportIsNotATransport(): void
    {
        $container = new InMemoryContainer();
        $container->setService('failure', new class () {
        });

        $provider = new FailureSendersProvider($container, ['bart' => 'failure']);

        $this->expectException(ServiceNotFound::class);
        $this->expectExceptionMessage('A transport with the name "failure" is either not present in the DI container');
        $provider->get('bart');
    }

    public function testATransportWillBeReturnedWhenPresentInTheContainer(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $container = new InMemoryContainer();
        $container->setService('failure', $transport);

        $provider = new FailureSendersProvider($container, ['homer' => 'failure']);

        self::assertSame($transport, $provider->get('homer'));
    }
}
