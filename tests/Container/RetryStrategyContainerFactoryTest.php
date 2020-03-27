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

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testThatStrategyIsNotAvailableWhenTransportDoesNotSpecifyStrategy() : void
    {
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $this->container->get('config')->willReturn([
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
        $locator = $factory($this->container->reveal());
        $this->assertFalse($locator->has('my_transport'));
    }

    public function testThatStrategyIsAvailableWhenTransportDoesSpecifyStrategy() : void
    {
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $this->container->get('config')->willReturn([
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
        $locator = $factory($this->container->reveal());
        $this->assertTrue($locator->has('my_transport'));
    }
}
