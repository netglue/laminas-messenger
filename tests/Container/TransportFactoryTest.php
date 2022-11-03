<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactoryTest extends TestCase
{
    private TransportInterface $transport;
    private TransportFactoryInterface $factory;
    /** @var MockObject&ContainerInterface */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->transport = new class () implements TransportInterface {
            // phpcs:ignore
            public function get() : iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function send(Envelope $envelope): Envelope
            {
                return $envelope;
            }
        };
        $this->factory = new class ($this->transport) implements TransportFactoryInterface {
            public SerializerInterface|null $serializer = null;
            public string|null $dsn = null;
            public array|null $options = null;

            public function __construct(private TransportInterface $transport)
            {
            }

            // phpcs:ignore
            public function createTransport(string $dsn, array $options, SerializerInterface $serializer) : TransportInterface
            {
                $this->serializer = $serializer;
                $this->dsn = $dsn;
                $this->options = $options;

                return $this->transport;
            }

            // phpcs:ignore
            public function supports(string $dsn, array $options) : bool
            {
                return true;
            }
        };
    }

    private function thereIsNoConfig(): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(false);
    }

    public function testThatAnExceptionIsThrownWhenNoDSNCanBeFound(): void
    {
        $this->thereIsNoConfig();
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('There is no DSN configured for the transport with name "foo"');
        TransportFactory::__callStatic('foo', [$this->container]);
    }

    private function factoryMock(): TransportFactoryFactory
    {
        $factoryFactory = $this->createMock(TransportFactoryFactory::class);
        $factoryFactory
            ->method('__invoke')
            ->willReturn($this->factory);

        return $factoryFactory;
    }

    /** @param mixed[] $config */
    private function injectConfigAndFactory(array $config): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->inject([
            ['config', $config],
            [TransportFactoryFactory::class, $this->factoryMock()],
        ]);
    }

    /** @param list<list<mixed>> $map */
    private function inject(array $map): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('get')
            ->willReturnMap($map);
    }

    /** @param mixed[] $config */
    private function injectConfigFactoryAndSerializer(array $config, PhpSerializer $serializer): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->inject([
            ['config', $config],
            [TransportFactoryFactory::class, $this->factoryMock()],
            ['MySerializer', $serializer],
        ]);
    }

    public function testThatTransportCanBeConfiguredWithStringAsDsn(): void
    {
        $dsn = 'My DSN!';
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = $dsn;
        $this->injectConfigAndFactory($config);
        $result = TransportFactory::__callStatic('foo', [$this->container]);

        self::assertSame($this->transport, $result);
        self::assertSame($dsn, $this->factory->dsn);
        self::assertEquals([], $this->factory->options);
        self::assertInstanceOf(PhpSerializer::class, $this->factory->serializer);
    }

    public function testThatTransportCanBeConfiguredWithCustomOptions(): void
    {
        $options = ['foo' => 'bar'];
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = [
            'dsn' => 'foo://bar',
            'options' => $options,
        ];
        $this->injectConfigAndFactory($config);
        $result = TransportFactory::__callStatic('foo', [$this->container]);

        self::assertSame($this->transport, $result);
        self::assertSame('foo://bar', $this->factory->dsn);
        self::assertEquals($options, $this->factory->options);
        self::assertInstanceOf(PhpSerializer::class, $this->factory->serializer);
    }

    public function testSerializerWillBeRetrievedFromContainerIfSpecified(): void
    {
        $serializer = new PhpSerializer();
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = [
            'dsn' => 'foo://bar',
            'serializer' => 'MySerializer',
        ];
        $this->injectConfigFactoryAndSerializer($config, $serializer);
        $result = TransportFactory::__callStatic('foo', [$this->container]);

        self::assertSame($this->transport, $result);
        self::assertSame('foo://bar', $this->factory->dsn);
        self::assertEquals([], $this->factory->options);
        self::assertSame($serializer, $this->factory->serializer);
    }
}
