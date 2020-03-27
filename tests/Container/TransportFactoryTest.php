<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactoryTest extends TestCase
{
    /** @var TransportInterface */
    private $transport;

    /** @var TransportFactoryInterface */
    private $factory;

    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->transport = new class() implements TransportInterface {
            // phpcs:ignore
            public function get() : iterable
            {
                return [];
            }

            public function ack(Envelope $envelope) : void
            {
            }

            public function reject(Envelope $envelope) : void
            {
            }

            public function send(Envelope $envelope) : Envelope
            {
                return $envelope;
            }
        };
        $this->factory = new class($this->transport) implements TransportFactoryInterface {
            /** @var TransportInterface */
            private $transport;
            /** @var SerializerInterface */
            public $serializer;
            /** @var string */
            public $dsn;
            /** @var mixed[] */
            public $options;

            public function __construct(TransportInterface $transport)
            {
                $this->transport = $transport;
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

    public function testThatAnExceptionIsThrownWhenNoDSNCanBeFound() : void
    {
        $this->container->has('config')->willReturn(false);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('There is no DSN configured for the transport with name "foo"');
        TransportFactory::__callStatic('foo', [$this->container->reveal()]);
    }

    /** @param mixed[] $config */
    private function injectConfig(array $config) : void
    {
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $this->container->get('config')->shouldBeCalled()->willReturn($config);
    }

    private function injectFactory() : void
    {
        $factoryFactory = $this->prophesize(TransportFactoryFactory::class);
        $factoryFactory->__invoke(Argument::type('string'), Argument::type(ContainerInterface::class))
            ->shouldBeCalled()
            ->willReturn($this->factory);
        $this->container->get(TransportFactoryFactory::class)
            ->shouldBeCalled()
            ->willReturn($factoryFactory->reveal());
    }

    public function testThatTransportCanBeConfiguredWithStringAsDsn() : void
    {
        $dsn = 'My DSN!';
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = $dsn;
        $this->injectConfig($config);
        $this->injectFactory();
        $result = TransportFactory::__callStatic('foo', [$this->container->reveal()]);

        $this->assertSame($this->transport, $result);
        $this->assertSame($dsn, $this->factory->dsn);
        $this->assertEquals([], $this->factory->options);
        $this->assertInstanceOf(PhpSerializer::class, $this->factory->serializer);
    }

    public function testThatTransportCanBeConfiguredWithCustomOptions() : void
    {
        $options = ['foo' => 'bar'];
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = [
            'dsn' => 'foo://bar',
            'options' => $options,
        ];
        $this->injectConfig($config);
        $this->injectFactory();
        $result = TransportFactory::__callStatic('foo', [$this->container->reveal()]);

        $this->assertSame($this->transport, $result);
        $this->assertSame('foo://bar', $this->factory->dsn);
        $this->assertEquals($options, $this->factory->options);
        $this->assertInstanceOf(PhpSerializer::class, $this->factory->serializer);
    }

    public function testSerializerWillBeRetrievedFromContainerIfSpecified() : void
    {
        $serializer = new PhpSerializer();
        $this->container->get('MySerializer')->shouldBeCalled()->willReturn($serializer);
        $config = [];
        $config['symfony']['messenger']['transports']['foo'] = [
            'dsn' => 'foo://bar',
            'serializer' => 'MySerializer',
        ];
        $this->injectConfig($config);
        $this->injectFactory();
        $result = TransportFactory::__callStatic('foo', [$this->container->reveal()]);

        $this->assertSame($this->transport, $result);
        $this->assertSame('foo://bar', $this->factory->dsn);
        $this->assertEquals([], $this->factory->options);
        $this->assertSame($serializer, $this->factory->serializer);
    }
}
