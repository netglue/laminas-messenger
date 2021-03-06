<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\RedisExt\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;

class TransportFactoryFactoryTest extends TestCase
{
    /** @var MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /** @return string[][] */
    public function transportDataProvider(): iterable
    {
        yield 'AMQP' => ['amqp://guest:guest@localhost:5672/%2f/messages', AmqpTransportFactory::class];

        yield 'Doctrine' => ['doctrine://default', DoctrineTransportFactory::class];

        yield 'In Memory' => ['in-memory://', InMemoryTransportFactory::class];

        yield 'Redis' => ['redis://', RedisTransportFactory::class];
    }

    /** @dataProvider transportDataProvider */
    public function testThatFactoryReturnsExpectedClass(string $dsn, string $expectedClass): void
    {
        $factory = new TransportFactoryFactory();
        $result = $factory($dsn, $this->container);
        self::assertInstanceOf($expectedClass, $result);
    }

    public function testThatSyncTransportIsCreatedWithRequiredBus(): void
    {
        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('message_bus')
            ->willReturn(new MessageBus());

        $factory = new TransportFactoryFactory();
        $result = $factory('sync://message_bus', $this->container);
        self::assertInstanceOf(SyncTransportFactory::class, $result);
    }

    public function testExceptionThrownForUnknownTransportScheme(): void
    {
        $factory = new TransportFactoryFactory();
        $this->expectException(UnknownTransportScheme::class);
        $this->expectExceptionMessage('The scheme/prefix "blargh" is not a known type of transport, or one that this library cannot handle');
        $factory('blargh://baz', $this->container);
    }
}
