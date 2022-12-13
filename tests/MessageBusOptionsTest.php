<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToOneFqcnContainerHandlerLocator;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

use function is_a;

class MessageBusOptionsTest extends TestCase
{
    private MessageBusOptions $options;

    protected function setUp(): void
    {
        parent::setUp();

        $this->options = new MessageBusOptions();
    }

    public function testSetAndGetHandlers(): void
    {
        self::assertSame([], $this->options->handlers());
        $this->options->setHandlers(['foo']);
        self::assertSame(['foo'], $this->options->handlers());
    }

    public function testSetAndGetMiddleware(): void
    {
        self::assertSame([], $this->options->middleware());
        $this->options->setMiddleware(['foo']);
        self::assertSame(['foo'], $this->options->middleware());
    }

    public function testSetAndGetRoutes(): void
    {
        self::assertSame([], $this->options->routes());
        $this->options->setRoutes(['foo']);
        self::assertSame(['foo'], $this->options->routes());
    }

    public function testSetAndGetZeroHandlerFlag(): void
    {
        $this->options->setAllowsZeroHandlers(true);
        self::assertTrue($this->options->allowsZeroHandlers());
        $this->options->setAllowsZeroHandlers(false);
        self::assertFalse($this->options->allowsZeroHandlers());
    }

    public function testHandlerLocatorHasDefaultValue(): void
    {
        self::assertTrue(
            is_a($this->options->handlerLocator(), HandlersLocatorInterface::class, true),
        );
    }

    public function testExceptionThrownSettingHandlerLocatorToInvalidClassName(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Handler locators must implement');
        $this->options->setHandlerLocator(self::class);
    }

    /** @return mixed[] */
    public function handlerLocatorTypes(): iterable
    {
        return [
            [OneToOneFqcnContainerHandlerLocator::class],
            [OneToManyFqcnContainerHandlerLocator::class],
            [HandlersLocator::class],
            [HandlersLocatorInterface::class],
        ];
    }

    /** @dataProvider handlerLocatorTypes */
    public function testValidKnownLocatorTypes(string $type): void
    {
        $this->options->setHandlerLocator($type);
        self::assertSame($type, $this->options->handlerLocator());
    }
}
