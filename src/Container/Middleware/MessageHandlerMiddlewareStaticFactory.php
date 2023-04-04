<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\Util;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class MessageHandlerMiddlewareStaticFactory
{
    /** @param non-empty-string $busIdentifier */
    public function __construct(private string $busIdentifier)
    {
    }

    public function __invoke(ContainerInterface $container): HandleMessageMiddleware
    {
        $options = Util::messageBusOptions($container, $this->busIdentifier);
        $locatorClass = $options->handlerLocator();
        $locator = $container->has($locatorClass) ? $container->get($locatorClass) : null;

        if (! $locator) {
            $locator = new $locatorClass($options->handlers(), $container);
        }

        $middleware = new HandleMessageMiddleware($locator, $options->allowsZeroHandlers());
        if ($options->logger()) {
            $middleware->setLogger(
                $container->get($options->logger()),
            );
        }

        return $middleware;
    }

    /**
     * @param non-empty-string $name
     * @param mixed[]          $arguments
     */
    public static function __callStatic(string $name, array $arguments): HandleMessageMiddleware
    {
        $container = Util::assertStaticFactoryContainer($name, $arguments);

        return (new self($name))($container);
    }
}
