<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToOneFqcnContainerHandlerLocator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use function iterator_to_array;

class OneToOneFqcnContainerHandlerLocatorTest extends TestCase
{
    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function injectMessageHandlerPair(string $handlerName) : void
    {
        $this->container->get($handlerName)
            ->shouldBeCalled()
            ->willReturn(static function () : void {
            });
    }

    public function testDescriptorWillBeReturnedWhenThereIsAMatch() : void
    {
        $this->injectMessageHandlerPair('myHandler');
        $map = [stdClass::class => 'myHandler'];
        $locator = new OneToOneFqcnContainerHandlerLocator($map, $this->container->reveal());

        $envelope = Envelope::wrap(new stdClass());
        $descriptors = iterator_to_array($locator->getHandlers($envelope));
        $this->assertCount(1, $descriptors);
        $this->assertContainsOnlyInstancesOf(HandlerDescriptor::class, $descriptors);
    }

    public function testDescriptorWillNotBeReturnedWhenThereIsNoMatch() : void
    {
        $map = [self::class => 'myHandler'];
        $locator = new OneToOneFqcnContainerHandlerLocator($map, $this->container->reveal());

        $envelope = Envelope::wrap(new stdClass());
        $descriptors = iterator_to_array($locator->getHandlers($envelope));
        $this->assertCount(0, $descriptors);
    }

    public function testExceptionThrownWhenHandlerIsNotAString() : void
    {
        $map = ['whatever' => ['foo' => 'bar']];
        $locator = new OneToOneFqcnContainerHandlerLocator($map, $this->container->reveal());
        $envelope = Envelope::wrap(new stdClass());
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('Handler should be a string representing a single handler to retrieve from the container');
        // phpcs:ignore
        foreach ($locator->getHandlers($envelope) as $description) {
            // noop
        }
    }
}
