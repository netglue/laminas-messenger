<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\MessageBusOptionsRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function assert;

class MessageBusOptionsRetrievalBehaviourTest extends TestCase
{
    /** @var object */
    private $subject;

    /** @var MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new class () {
            use MessageBusOptionsRetrievalBehaviour;

            public function getOptions(ContainerInterface $container, string $id): MessageBusOptions
            {
                return $this->options($container, $id);
            }
        };

        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function thereIsNoConfig(): void
    {
        $this->container->expects(self::atLeast(1))
            ->method('has')
            ->with('config')
            ->willReturn(false);
    }

    public function testOptionsAreReturnedWhenThereIsNoConfig(): void
    {
        $this->thereIsNoConfig();

        $options = $this->subject->getOptions($this->container, 'foo');
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
            'framework' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => ['logger' => 'MyLogger'],
                    ],
                ],
            ],
        ]);

        $options = $this->subject->getOptions($this->container, 'my_bus');
        assert($options instanceof MessageBusOptions);
        self::assertSame('MyLogger', $options->logger());
    }
}
