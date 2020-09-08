<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\RetryStrategyContainerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class RetryStrategyContainerFactoryTest extends TestCase
{
    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /** @param mixed[] $config */
    private function configWillBe(array $config): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $this->container->expects(self::atLeast(1))
            ->method('get')
            ->with('config')
            ->willReturn($config);
    }

    public function testThatStrategyIsNotAvailableWhenTransportDoesNotSpecifyStrategy(): void
    {
        $this->configWillBe([
            'symfony' => [
                'messenger' => [
                    'transports' => [
                        'my_transport' => [
                            'dsn' => 'sync://',
                            'options' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new RetryStrategyContainerFactory();
        $locator = $factory($this->container);
        self::assertFalse($locator->has('my_transport'));
    }

    public function testThatStrategyIsAvailableWhenTransportDoesSpecifyStrategy(): void
    {
        $this->configWillBe([
            'symfony' => [
                'messenger' => [
                    'transports' => [
                        'my_transport' => [
                            'dsn' => 'sync://',
                            'retry_strategy' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new RetryStrategyContainerFactory();
        $locator = $factory($this->container);
        self::assertTrue($locator->has('my_transport'));
    }
}
