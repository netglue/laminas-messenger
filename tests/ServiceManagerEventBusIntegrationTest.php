<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
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
use Symfony\Component\Messenger\MessageBus;

use function assert;

class ServiceManagerEventBusIntegrationTest extends TestCase
{
    /** @var mixed[] */
    private array $config;

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

        return $aggregator->getMergedConfig();
    }

    private function assertMessageBus(ContainerInterface $container, string $id): MessageBus
    {
        $bus = $container->get($id);
        assert($bus instanceof MessageBus);

        return $bus;
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
        $this->assertFalse($listenerOne->triggered);
        $this->assertFalse($listenerTwo->triggered);

        $this->config['symfony']['messenger']['buses']['event_bus']['handlers'] = [
            TestEvent::class => [
                EventListenerOne::class,
                EventListenerTwo::class,
            ],
        ];
        $this->config['dependencies']['factories'][EventListenerOne::class] = static function () use ($listenerOne) {
            return $listenerOne;
        };
        $this->config['dependencies']['factories'][EventListenerTwo::class] = static function () use ($listenerTwo) {
            return $listenerTwo;
        };

        $container = $this->container();

        $bus = $this->assertMessageBus($container, 'event_bus');
        $bus->dispatch(new TestEvent());

        $this->consumeOne($container, 'my_transport');

        $this->assertTrue($listenerOne->triggered);
        $this->assertTrue($listenerTwo->triggered);
    }
}
