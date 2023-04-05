<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\Middleware\MessageHandlerMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class MessageHandlerMiddlewareStaticFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private MessageHandlerMiddlewareStaticFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory = new MessageHandlerMiddlewareStaticFactory('my_bus');
    }

    public function testThatMiddlewareIsProducedWhenTheLocatorIsDefinedInTheContainer(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => [
                            'handler_locator' => OneToManyFqcnContainerHandlerLocator::class,
                        ],
                    ],
                ],
            ],
        ]);

        $locator = $this->createMock(HandlersLocatorInterface::class);

        $this->container->setService(OneToManyFqcnContainerHandlerLocator::class, $locator);

        $middleware = $this->factory->__invoke($this->container);

        self::assertInstanceOf(HandleMessageMiddleware::class, $middleware);
    }

    public function testThatMiddlewareIsProducedWhenTheLocatorIsNotDefinedInTheContainer(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => [
                            'handler_locator' => OneToManyFqcnContainerHandlerLocator::class,
                        ],
                    ],
                ],
            ],
        ]);

        $middleware = $this->factory->__invoke($this->container);
        self::assertInstanceOf(HandleMessageMiddleware::class, $middleware);
    }

    public function testThatMiddlewareIsProducedWhenTheLocatorIsCompletelyUndefined(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => [],
                    ],
                ],
            ],
        ]);

        $middleware = $this->factory->__invoke($this->container);
        self::assertInstanceOf(HandleMessageMiddleware::class, $middleware);
    }

    public function testThatTheDefinedLoggerWillBeComposed(): void
    {
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => [
                    'buses' => [
                        'my_bus' => ['logger' => 'myLogger'],
                    ],
                ],
            ],
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $this->container->setService('myLogger', $logger);
        $middleware = $this->factory->__invoke($this->container);
        $prop = new ReflectionProperty($middleware, 'logger');
        self::assertSame($logger, $prop->getValue($middleware));
    }
}
