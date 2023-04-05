<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\FailureReceiversProviderFactory;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;

class FailureReceiversProviderFactoryTest extends TestCase
{
    private InMemoryContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
    }

    public function testThatTheGlobalFailureTransportWillBeAvailable(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => 'failed',
                    'transports' => [],
                ],
            ],
        ]);

        $provider = (new FailureReceiversProviderFactory())->__invoke($this->container);

        self::assertTrue($provider->has('failed'));
    }

    public function testThatAdditionalTransportsWillBeAvailable(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => 'failed',
                    'transports' => [
                        'failed' => 'sync://failed',
                        'events' => [
                            'dsn' => 'sync://whatever',
                            'failure_transport' => 'other-failed',
                        ],
                        'other-failed' => 'sync://other-failed',
                    ],
                ],
            ],
        ]);

        $provider = (new FailureReceiversProviderFactory())->__invoke($this->container);

        self::assertTrue($provider->has('failed'));
        self::assertTrue($provider->has('other-failed'));
        self::assertFalse($provider->has('events'));
    }

    public function testThatDuplicateTransportsWillNotCauseAnIssue(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'failure_transport' => 'failed',
                    'transports' => [
                        'failed' => 'sync://failed',
                        'events' => [
                            'dsn' => 'sync://whatever',
                            'failure_transport' => 'other-failed',
                        ],
                        'command' => [
                            'dsn' => 'sync://whatever',
                            'failure_transport' => 'other-failed',
                        ],
                        'other-failed' => 'sync://other-failed',
                    ],
                ],
            ],
        ]);

        $provider = (new FailureReceiversProviderFactory())->__invoke($this->container);

        self::assertTrue($provider->has('failed'));
        self::assertTrue($provider->has('other-failed'));
        self::assertFalse($provider->has('events'));
        self::assertFalse($provider->has('command'));
    }

    public function testThatZeroFailureTransportsIsPossible(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'transports' => [
                        'events' => ['dsn' => 'sync://whatever'],
                        'command' => ['dsn' => 'sync://whatever'],
                    ],
                ],
            ],
        ]);

        $provider = (new FailureReceiversProviderFactory())->__invoke($this->container);

        self::assertFalse($provider->has('events'));
        self::assertFalse($provider->has('command'));
    }
}
