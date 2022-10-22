<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\DefaultCommandBusConfigProvider;
use Netglue\PsrContainer\Messenger\FailureCommandsConfigProvider;
use Netglue\PsrContainer\MessengerTest\Fixture\ExceptionalCommandHandler;
use Netglue\PsrContainer\MessengerTest\Fixture\TestCommand;
use Netglue\PsrContainer\MessengerTest\Fixture\TestCommandHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

use function assert;

class ServiceManagerIntegrationTest extends TestCase
{
    /** @var mixed[] */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->minimalCommandBusConfiguration();
    }

    private function container(): ServiceManager
    {
        $this->config['dependencies']['services']['config'] = $this->config;

        return new ServiceManager($this->config['dependencies']);
    }

    /** @return mixed[] */
    private function minimalCommandBusConfiguration(): array
    {
        $aggregator = new ConfigAggregator([
            ConfigProvider::class,
            DefaultCommandBusConfigProvider::class,
            new ArrayProvider([
                'framework' => [
                    'messenger' => [
                        'transports' => [
                            'my_transport' => ['dsn' => 'in-memory:///'],
                        ],
                        'buses' => [
                            'command_bus' => [
                                'routes' => [
                                    '*' => ['my_transport'],
                                ],
                            ],
                        ],
                    ],
                ],
                'dependencies' => [
                    'factories' => [
                        'my_transport' => [TransportFactory::class, 'my_transport'],
                    ],
                ],
            ]),
        ]);

        return $aggregator->getMergedConfig();
    }

    private function assertMessageBus(ContainerInterface $container, string $id): MessageBus
    {
        $bus = $container->get($id);
        assert($bus instanceof MessageBus);

        return $bus;
    }

    private function assertInMemoryTransport(ContainerInterface $container, string $id): InMemoryTransport
    {
        $transport = $container->get($id);
        assert($transport instanceof InMemoryTransport);

        return $transport;
    }

    public function testThatABusCanBeCreated(): void
    {
        $container = $this->container();
        self::assertTrue($container->has('command_bus'));
        self::assertInstanceOf(MessageBus::class, $container->get('command_bus'));
    }

    public function testThatMessageSentOnDefaultCommandBusIsRoutedToConfiguredTransport(): void
    {
        $this->config['framework']['messenger']['buses']['command_bus']['handlers'] = [
            TestCommand::class => TestCommandHandler::class,
        ];
        $this->config['dependencies']['factories'][TestCommandHandler::class] = InvokableFactory::class;

        $container = $this->container();
        $bus = $this->assertMessageBus($container, 'command_bus');
        $transport = $this->assertInMemoryTransport($container, 'my_transport');
        $bus->dispatch(new TestCommand());
        $envelopes = $transport->get();
        self::assertCount(1, $envelopes);
        $envelope = $envelopes[0];
        assert($envelope instanceof Envelope);
        self::assertInstanceOf(TestCommand::class, $envelope->getMessage());
    }

    private function setUpFailureTransport(): void
    {
        $this->config['framework']['messenger']['failure_transport'] = 'failure_transport';
        $this->config['framework']['messenger']['transports']['failure_transport'] = ['dsn' => 'in-memory:///'];
        $this->config['dependencies']['factories']['failure_transport'] = [TransportFactory::class, 'failure_transport'];
    }

    private function consumeOne(ContainerInterface $container, string $receiverTransport): void
    {
        $transport = $this->assertInMemoryTransport($container, $receiverTransport);
        $queued = $transport->get();
        self::assertCount(1, $queued, 'There should be 1 message to consume');

        $command = $container->get(ConsumeMessagesCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            '--limit' => 1,
            'receivers' => [$receiverTransport],
        ]);

        self::assertEquals(0, $tester->getStatusCode());
        $queued = $transport->get();
        self::assertCount(0, $queued, 'All messages should have been consumed');
    }

    public function testThatFailedMessagesWillBeSentToFailureTransportWhenConfigured(): void
    {
        $this->setUpFailureTransport();
        $this->config['framework']['messenger']['buses']['command_bus']['handlers'] = [
            TestCommand::class => ExceptionalCommandHandler::class,
        ];

        $container = $this->container();
        $bus = $this->assertMessageBus($container, 'command_bus');
        $bus->dispatch(new TestCommand());
        $this->consumeOne($container, 'my_transport');

        $failure = $this->assertInMemoryTransport($container, 'failure_transport');
        $envelopes = $failure->get();
        self::assertCount(1, $envelopes, 'There should be 1 message stored in the failure queue');
        $envelope = $envelopes[0];
        assert($envelope instanceof Envelope);
        self::assertInstanceOf(TestCommand::class, $envelope->getMessage());
    }

    /** @return mixed[] */
    public function failureCommandNames(): iterable
    {
        return [
            FailedMessagesRemoveCommand::class => [FailedMessagesRemoveCommand::class],
            FailedMessagesRetryCommand::class => [FailedMessagesRetryCommand::class],
            FailedMessagesShowCommand::class => [FailedMessagesShowCommand::class],
        ];
    }

    /** @dataProvider failureCommandNames */
    public function testThatAnExceptionWillBeThrownWhenTheFailureCommandsAreRegisteredWithoutAFailureTransportAvailable(string $commandName): void
    {
        $aggregator = new ConfigAggregator([
            FailureCommandsConfigProvider::class,
            new ArrayProvider($this->config),
        ]);
        $this->config = $aggregator->getMergedConfig();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('No failure transport has been specified');
        $this->assertMessageBus($this->container(), FailedMessagesRemoveCommand::class);
    }
}
