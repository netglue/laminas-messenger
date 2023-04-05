<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;

use function assert;

final class MessageSenderMiddlewareStaticFactory
{
    /** @param non-empty-string $busIdentifier */
    public function __construct(private readonly string $busIdentifier)
    {
    }

    public function __invoke(ContainerInterface $container): SendMessageMiddleware
    {
        $options = Util::messageBusOptions($container, $this->busIdentifier);

        $transportRouter = new SendersLocator(
            $options->routes(),
            $container,
        );

        $middleware = new SendMessageMiddleware($transportRouter);
        if ($options->logger()) {
            $logger = $container->get($options->logger());
            assert($logger instanceof LoggerInterface);
            $middleware->setLogger($logger);
        }

        return $middleware;
    }

    /**
     * @param non-empty-string $name
     * @param mixed[]          $arguments
     */
    public static function __callStatic(string $name, array $arguments): SendMessageMiddleware
    {
        $container = Util::assertStaticFactoryContainer($name, $arguments);

        return (new self($name))($container);
    }
}
