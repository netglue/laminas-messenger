<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\DefaultEventBusConfigProvider;
use Netglue\PsrContainer\MessengerTest\Fixture\EventListenerOne;
use Netglue\PsrContainer\MessengerTest\Fixture\EventListenerTwo;
use Netglue\PsrContainer\MessengerTest\Fixture\TestEvent;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;

/**
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 * @psalm-type TestConfig = array{
 *     symfony: array{
 *         messenger: array{
 *             transports: array<string, array>,
 *             buses: array{
 *                 event_bus: array{
 *                     routes: array<string, list<string>>,
 *                     handlers: array<string, list<string>>,
 *                 },
 *             },
 *         },
 *     },
 *     dependencies: ServiceManagerConfigurationType,
 * }
 */
class ServiceManagerEventBusIntegrationTest extends TestCase
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
        /** @psalm-suppress PossiblyNullArrayAccess */
        unset($this->config['dependencies']['services']['config']);
        $this->config['dependencies']['services']['config'] = $this->config;

        return new ServiceManager($this->config['dependencies']);
    }

    /** @return TestConfig */
    private function minimalCommandBusConfiguration(): array
    {
        $aggregator = new ConfigAggregator([
            ConfigProvider::class,
            DefaultEventBusConfigProvider::class,
            new ArrayProvider([
                'symfony' => [
                    'messenger' => [
                        'transports' => [
                            'my_transport' => ['dsn' => 'in-memory:///'],
                        ],
                        'buses' => [
                            'event_bus' => [
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

    private function consumeOne(ContainerInterface $container, string $receiverTransport): void
    {
        $command = $container->get(ConsumeMessagesCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            '--limit' => 1,
            'receivers' => [$receiverTransport],
        ]);
    }

    public function testThatEventSentOnDefaultEventIsHandledByAllListeners(): void
    {
        $listenerOne = new EventListenerOne();
        $listenerTwo = new EventListenerTwo();
        self::assertFalse($listenerOne->triggered);
        self::assertFalse($listenerTwo->triggered);

        $this->config['symfony']['messenger']['buses']['event_bus']['handlers'] = [
            TestEvent::class => [
                EventListenerOne::class,
                EventListenerTwo::class,
            ],
        ];

        /** @psalm-suppress PossiblyNullArrayAccess */
        unset($this->config['dependencies']['factories'][EventListenerOne::class]);
        $this->config['dependencies']['factories'][EventListenerOne::class]
            = static fn (): EventListenerOne => $listenerOne;
        $this->config['dependencies']['factories'][EventListenerTwo::class]
            = static fn (): EventListenerTwo => $listenerTwo;

        $container = $this->container();

        $bus = ServiceManagerIntegrationTest::assertMessageBus($container, 'event_bus');
        $bus->dispatch(new TestEvent());

        $this->consumeOne($container, 'my_transport');

        self::assertTrue($listenerOne->triggered);
        self::assertTrue($listenerTwo->triggered);
    }
}
