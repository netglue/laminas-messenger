<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\Util;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MessageBusOptionsRetrievalBehaviourTest extends TestCase
{
    /** @var ContainerInterface&MockObject  */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testOptionsAreReturnedWhenThereIsNoConfig(): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $options = Util::messageBusOptions($this->container, 'foo');
        $emptyOptions = new MessageBusOptions();

        self::assertEquals($emptyOptions->toArray(), $options->toArray());
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

    public function testOptionsWillBeRelevantToTheBusIdentifierProvided(): void
    {
        $this->configWillBe([
            'symfony' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => ['logger' => 'MyLogger'],
                    ],
                ],
            ],
        ]);

        $options = Util::messageBusOptions($this->container, 'my_bus');
        self::assertSame('MyLogger', $options->logger());
    }
}
