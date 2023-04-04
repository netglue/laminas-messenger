<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\DefaultCommandBusConfigProvider;
use Netglue\PsrContainer\Messenger\FailureCommandsConfigProvider;
use Netglue\PsrContainer\MessengerTest\Fixture\ExceptionalCommandHandler;
use Netglue\PsrContainer\MessengerTest\Fixture\TestCommand;
use Netglue\PsrContainer\MessengerTest\Fixture\TestCommandHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

use function assert;

/**
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 * @psalm-type TestConfig = array{
 *     symfony: array{
 *         messenger: array{
 *             failure_transport?: string|null,
 *             transports: array<string, array>,
 *             buses: array{
 *                 command_bus: array{
 *                     routes: array<string, list<string>>,
 *                     handlers?: array,
 *                 },
 *             },
 *         },
 *     },
 *     dependencies: ServiceManagerConfigurationType,
 * }
 */
class ServiceManagerIntegrationTest extends TestCase
{
    /** @var TestConfig */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->minimalCommandBusConfiguration();
    }

    private function container(): ServiceManager
    {
        unset($this->config['dependencies']['services']['config']);
        $this->config['dependencies']['services']['config'] = $this->config;

        return new ServiceManager($this->config['dependencies']);
    }

    /** @return TestConfig */
    private function minimalCommandBusConfiguration(): array
    {
        $aggregator = new ConfigAggregator([
            ConfigProvider::class,
            DefaultCommandBusConfigProvider::class,
            new ArrayProvider([
                'symfony' => [
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

        /** @psalm-var TestConfig */

        return $aggregator->getMergedConfig();
    }

    public static function assertMessageBus(ContainerInterface $container, string $id): MessageBus
    {
        $bus = $container->get($id);
        assert($bus instanceof MessageBus);

        return $bus;
    }

    private static function assertInMemoryTransport(ContainerInterface $container, string $id): InMemoryTransport
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
        $this->config['symfony']['messenger']['buses']['command_bus']['handlers'] = [
            TestCommand::class => TestCommandHandler::class,
        ];
        unset($this->config['dependencies']['factories'][TestCommandHandler::class]);
        $this->config['dependencies']['factories'][TestCommandHandler::class] = InvokableFactory::class;

        $container = $this->container();
        $bus = self::assertMessageBus($container, 'command_bus');
        $transport = self::assertInMemoryTransport($container, 'my_transport');
        $bus->dispatch(new TestCommand());
        $envelopes = $transport->get();
        self::assertCount(1, $envelopes);
        $envelope = $envelopes[0];
        self::assertInstanceOf(TestCommand::class, $envelope->getMessage());
    }

    private function setUpFailureTransport(): void
    {
        $this->config['symfony']['messenger']['failure_transport'] = 'failure_transport';
        $this->config['symfony']['messenger']['transports']['failure_transport'] = ['dsn' => 'in-memory:///'];
        unset($this->config['dependencies']['factories']['failure_transport']);
        $this->config['dependencies']['factories']['failure_transport'] = [TransportFactory::class, 'failure_transport'];
    }

    private function consumeOne(ContainerInterface $container, string $receiverTransport): void
    {
        $transport = self::assertInMemoryTransport($container, $receiverTransport);
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
        $this->config['symfony']['messenger']['buses']['command_bus']['handlers'] = [
            TestCommand::class => ExceptionalCommandHandler::class,
        ];

        $container = $this->container();
        $bus = self::assertMessageBus($container, 'command_bus');
        $bus->dispatch(new TestCommand());
        $this->consumeOne($container, 'my_transport');

        $failure = self::assertInMemoryTransport($container, 'failure_transport');
        $envelopes = $failure->get();
        self::assertCount(1, $envelopes, 'There should be 1 message stored in the failure queue');
        $envelope = $envelopes[0];
        self::assertInstanceOf(TestCommand::class, $envelope->getMessage());
    }

    /** @return array<class-string, array{0: class-string}> */
    public static function failureCommandNames(): iterable
    {
        return [
            FailedMessagesRemoveCommand::class => [FailedMessagesRemoveCommand::class],
            FailedMessagesRetryCommand::class => [FailedMessagesRetryCommand::class],
            FailedMessagesShowCommand::class => [FailedMessagesShowCommand::class],
        ];
    }

    #[DataProvider('failureCommandNames')]
    public function testThatAnExceptionWillBeThrownWhenTheFailureCommandsAreRegisteredWithoutAFailureTransportAvailable(): void
    {
        $aggregator = new ConfigAggregator([
            FailureCommandsConfigProvider::class,
            new ArrayProvider($this->config),
        ]);
        /** @psalm-var TestConfig $this->config */
        $this->config = $aggregator->getMergedConfig();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('No failure transport has been specified');
        self::assertMessageBus($this->container(), FailedMessagesRemoveCommand::class);
    }
}
