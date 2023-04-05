<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\FailureSendersProviderFactory;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\TransportInterface;

class FailureSendersProviderFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private FailureSendersProviderFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory = new FailureSendersProviderFactory();
    }

    public function testThatFailureSendersCanBeEmpty(): void
    {
        $this->container->setService('config', []);
        $provider = $this->factory->__invoke($this->container);

        self::assertFalse($provider->has('anything'));
        self::assertEquals([], $provider->getProvidedServices());
    }

    public function testThatFailureSendersIsEmptyWhenThereIsNoFailureTransportAtAll(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'transports' => [
                        'my_transport' => ['dsn' => 'in-memory:///'],
                    ],
                ],
            ],
        ]);
        $provider = $this->factory->__invoke($this->container);

        self::assertFalse($provider->has('my_transport'));
        self::assertEquals([], $provider->getProvidedServices());
    }

    public function testTheGlobalFailureReceiverIsReturnedWhenSpecified(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => 'default_failure',
                    'transports' => [
                        'my_transport' => ['dsn' => 'in-memory:///'],
                        'default_failure' => ['dsn' => 'in-memory:///'],
                    ],
                ],
            ],
        ]);
        $transport = $this->createMock(TransportInterface::class);
        $this->container->setService('default_failure', $transport);

        $provider = $this->factory->__invoke($this->container);

        self::assertTrue($provider->has('my_transport'));
        self::assertSame($transport, $provider->get('my_transport'));
    }

    public function testThatASpecificTransportWillOverrideTheGlobalTransport(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => 'default_failure',
                    'transports' => [
                        'events' => ['dsn' => 'in-memory:///'],
                        'commands' => [
                            'dsn' => 'in-memory:///',
                            'failure_transport' => 'command_failures',
                        ],
                        'default_failure' => ['dsn' => 'in-memory:///'],
                        'command_failures' => ['dsn' => 'in-memory:///'],
                    ],
                ],
            ],
        ]);
        $default = $this->createMock(TransportInterface::class);
        $commands = $this->createMock(TransportInterface::class);
        $this->container->setService('default_failure', $default);
        $this->container->setService('command_failures', $commands);

        $provider = $this->factory->__invoke($this->container);

        self::assertTrue($provider->has('commands'));
        self::assertTrue($provider->has('events'));
        self::assertSame($commands, $provider->get('commands'));
        self::assertNotSame($default, $provider->get('commands'));
        self::assertSame($default, $provider->get('events'));
    }

    public function testThatTheDefaultFailureTransportCanBeOmitted(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => null,
                    'transports' => [
                        'events' => ['dsn' => 'in-memory:///'],
                        'commands' => [
                            'dsn' => 'in-memory:///',
                            'failure_transport' => 'command_failures',
                        ],
                        'command_failures' => ['dsn' => 'in-memory:///'],
                    ],
                ],
            ],
        ]);

        $commands = $this->createMock(TransportInterface::class);
        $this->container->setService('command_failures', $commands);

        $provider = $this->factory->__invoke($this->container);

        self::assertTrue($provider->has('commands'));
        self::assertSame($commands, $provider->get('commands'));

        self::assertFalse($provider->has('events'));
    }
}
