<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

class TransportFactoryFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    /** @return iterable<string, array{0: non-empty-string, 1: class-string}> */
    public static function transportDataProvider(): iterable
    {
        yield 'AMQP' => ['amqp://guest:guest@localhost:5672/%2f/messages', AmqpTransportFactory::class];

        yield 'Doctrine' => ['doctrine://default', DoctrineTransportFactory::class];

        yield 'In Memory' => ['in-memory://', InMemoryTransportFactory::class];

        yield 'Redis' => ['redis://', RedisTransportFactory::class];
    }

    /**
     * @param non-empty-string $dsn
     * @param class-string     $expectedClass
     */
    #[DataProvider('transportDataProvider')]
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

    public function testThatConfiguredFactoryReturnsFactory(): void
    {
        $dsn = 'valid://foo';
        $validFactory = $this->createStub(TransportFactoryInterface::class);
        $validFactory->method('supports')
            ->with($dsn, [])
            ->willReturn(true);

        $config = [
            'symfony' => [
                'messenger' => [
                    'transport_factories' => ['test-factory'],
                ],
            ],
        ];

        $this->container->method('has')
            ->with('config')
            ->willReturn(true);
        $this->container->method('get')
            ->willReturnMap([
                ['config', $config],
                ['test-factory', $validFactory],
            ]);

        $factory = new TransportFactoryFactory();
        $result = $factory('valid://foo', $this->container);
        self::assertSame($validFactory, $result);
    }

    public function testThatConfiguredInvalidFactoryThrowsConfigurationException(): void
    {
        $dsn = 'invalid://foo';
        $invalidFactory = new stdClass();

        $config = [
            'symfony' => [
                'messenger' => [
                    'transport_factories' => ['test-factory'],
                ],
            ],
        ];

        $this->container->method('has')
            ->with('config')
            ->willReturn(true);
        $this->container->method('get')
            ->willReturnMap([
                ['config', $config],
                ['test-factory', $invalidFactory],
            ]);

        $factory = new TransportFactoryFactory();

        $this->expectException(ConfigurationError::class);
        $factory($dsn, $this->container);
    }

    public function testExceptionThrownForUnknownTransportScheme(): void
    {
        $factory = new TransportFactoryFactory();
        $this->expectException(UnknownTransportScheme::class);
        $this->expectExceptionMessage('The scheme/prefix "blargh" is not a known type of transport, or one that this library cannot handle');
        $factory('blargh://baz', $this->container);
    }
}
