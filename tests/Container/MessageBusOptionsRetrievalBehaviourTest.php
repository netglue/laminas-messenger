<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Netglue\PsrContainer\Messenger\Container\MessageBusOptionsRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use function assert;

class MessageBusOptionsRetrievalBehaviourTest extends TestCase
{
    /** @var object */
    private $subject;

    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->subject = new class() {
            use MessageBusOptionsRetrievalBehaviour;

            public function getOptions(ContainerInterface $container, string $id) : MessageBusOptions
            {
                return $this->options($container, $id);
            }
        };

        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testOptionsAreReturnedWhenThereIsNoConfig() : void
    {
        $this->container->has('config')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->container->get('config')->shouldNotBeCalled();

        $options = $this->subject->getOptions($this->container->reveal(), 'foo');
        $emptyOptions = new MessageBusOptions();

        $this->assertEquals($emptyOptions->toArray(), $options->toArray());
    }

    public function testOptionsWillBeRelevantToTheBusIdentifierProvided() : void
    {
        $this->container->has('config')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->container->get('config')
            ->shouldBeCalled()
            ->willReturn([
                'symfony' => [
                    'messenger' => [
                        'buses' => [
                            'my_bus' => ['logger' => 'MyLogger'],
                        ],
                    ],
                ],
            ]);

        $options = $this->subject->getOptions($this->container->reveal(), 'my_bus');
        assert($options instanceof MessageBusOptions);
        $this->assertSame('MyLogger', $options->logger());
    }
}
