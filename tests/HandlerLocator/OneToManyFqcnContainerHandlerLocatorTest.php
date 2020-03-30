<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Netglue\PsrContainer\MessengerTest\Fixture\TestEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use function iterator_to_array;

class OneToManyFqcnContainerHandlerLocatorTest extends TestCase
{
    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testThatHandlersMustBeConfiguredAsAnArray() : void
    {
        $handlers = ['foo' => 'foo'];
        $locator = new OneToManyFqcnContainerHandlerLocator($handlers, $this->container->reveal());
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('Expected an array of handler identifiers to retrieve from the container');
        iterator_to_array($locator->getHandlers(new Envelope(new stdClass())));
    }

    public function testThatHandlersReturnedWillMatchConfigured() : void
    {
        $handlers = [TestEvent::class => ['handler1'], 'other' => ['handler2']];
        $handler1 = static function () : void {
        };
        $this->container->get('handler1')->shouldBeCalled()->willReturn($handler1);
        $this->container->get('handler2')->shouldNotBeCalled();
        $locator = new OneToManyFqcnContainerHandlerLocator($handlers, $this->container->reveal());
        foreach ($locator->getHandlers(new Envelope(new TestEvent())) as $descriptor) {
            $handler = $descriptor->getHandler();
            $this->assertSame($handler1, $handler);
        }
    }
}
