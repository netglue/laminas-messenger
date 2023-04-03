<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\MessageBusOptionsRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;

final class MessageSenderMiddlewareStaticFactory
{
    use MessageBusOptionsRetrievalBehaviour;
    use StaticFactoryContainerAssertion;

    public function __construct(private string $busIdentifier)
    {
    }

    public function __invoke(ContainerInterface $container): SendMessageMiddleware
    {
        $options = $this->options($container, $this->busIdentifier);

        $transportRouter = new SendersLocator(
            $options->routes(),
            $container,
        );

        $middleware = new SendMessageMiddleware($transportRouter);
        if ($options->logger()) {
            $middleware->setLogger(
                $container->get(
                    $options->logger(),
                ),
            );
        }

        return $middleware;
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $name, array $arguments): SendMessageMiddleware
    {
        $container = self::assertContainer($name, $arguments);

        return (new self($name))($container);
    }
}
