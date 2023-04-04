<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;

use function assert;

final class MessageBusStaticFactory
{
    /** @param non-empty-string $id */
    public function __construct(private string $id)
    {
    }

    public function __invoke(ContainerInterface $container): MessageBusInterface
    {
        $options = Util::messageBusOptions($container, $this->id);
        $middlewareNames = $options->middleware();
        $middleware = [];
        foreach ($middlewareNames as $name) {
            $service = $container->get($name);
            assert($service instanceof MiddlewareInterface);
            $middleware[] = $service;
        }

        return new MessageBus($middleware);
    }

    /**
     * @param non-empty-string $name
     * @param mixed[]          $arguments
     */
    public static function __callStatic(string $name, array $arguments): MessageBusInterface
    {
        $container = Util::assertStaticFactoryContainer($name, $arguments);

        return (new self($name))($container);
    }
}
