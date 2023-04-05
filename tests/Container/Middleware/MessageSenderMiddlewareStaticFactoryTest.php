<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\Middleware\MessageSenderMiddlewareStaticFactory;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;

class MessageSenderMiddlewareStaticFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private MessageSenderMiddlewareStaticFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
        $this->factory = new MessageSenderMiddlewareStaticFactory('my_bus');
    }

    public function testMiddlewareIsProducedWithZeroConfig(): void
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
        self::assertInstanceOf(SendMessageMiddleware::class, $middleware);
    }

    public function testMiddlewareIsComposedWithALoggerWhenConfigured(): void
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
