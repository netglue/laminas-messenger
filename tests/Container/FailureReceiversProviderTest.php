<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\FailureReceiversProvider;
use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailureReceiversProviderTest extends TestCase
{
    public function testThatProvidedServicesAreAllConsideredTransports(): void
    {
        $provider = new FailureReceiversProvider(
            $this->createMock(ContainerInterface::class),
            [
                'homer',
                'marge',
            ],
        );

        $result = $provider->getProvidedServices();
        self::assertEquals([
            'homer' => TransportInterface::class,
            'marge' => TransportInterface::class,
        ], $result);
    }

    public function testThatHasIsTrueWhenTheTransportNameIsKnown(): void
    {
        $provider = new FailureReceiversProvider(
            $this->createMock(ContainerInterface::class),
            [
                'homer',
                'marge',
            ],
        );

        self::assertTrue($provider->has('homer'));
        self::assertFalse($provider->has('maggie'));
    }

    public function testGetIsExceptionalWhenTheResultIsNotATransport(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('homer')
            ->willReturn(new class () {
            });

        $provider = new FailureReceiversProvider(
            $container,
            ['homer'],
        );

        $this->expectException(ServiceNotFound::class);
        $this->expectExceptionMessage('A transport with the name "homer" is either not present in the DI container');
        $provider->get('homer');
    }

    public function testGetWillSuccessfullyReturnAValidTransport(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $transport = $this->createMock(TransportInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with('homer')
            ->willReturn($transport);

        $provider = new FailureReceiversProvider(
            $container,
            ['homer'],
        );

        self::assertSame($transport, $provider->get('homer'));
    }
}
