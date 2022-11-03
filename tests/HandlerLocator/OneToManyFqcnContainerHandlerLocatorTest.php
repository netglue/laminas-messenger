<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Netglue\PsrContainer\MessengerTest\Fixture\TestEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;

use function iterator_to_array;

class OneToManyFqcnContainerHandlerLocatorTest extends TestCase
{
    private MockObject|ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testThatHandlersMustBeConfiguredAsAnArray(): void
    {
        $handlers = ['foo' => 'foo'];
        $locator = new OneToManyFqcnContainerHandlerLocator($handlers, $this->container);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('Expected an array of handler identifiers to retrieve from the container');
        iterator_to_array($locator->getHandlers(new Envelope(new stdClass())));
    }

    public function testThatHandlersReturnedWillMatchConfigured(): void
    {
        $handlers = [TestEvent::class => ['handler1'], 'other' => ['handler2']];
        $handler1 = static function (): void {
        };
        $this->container->expects(self::atLeast(1))
            ->method('get')
            ->with('handler1')
            ->willReturn($handler1);

        $locator = new OneToManyFqcnContainerHandlerLocator($handlers, $this->container);
        foreach ($locator->getHandlers(new Envelope(new TestEvent())) as $descriptor) {
            $handler = $descriptor->getHandler();
            self::assertSame($handler1, $handler);
        }
    }
}
