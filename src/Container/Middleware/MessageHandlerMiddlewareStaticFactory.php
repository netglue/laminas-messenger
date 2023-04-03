<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\MessageBusOptionsRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class MessageHandlerMiddlewareStaticFactory
{
    use MessageBusOptionsRetrievalBehaviour;
    use StaticFactoryContainerAssertion;

    public function __construct(private string $busIdentifier)
    {
    }

    public function __invoke(ContainerInterface $container): HandleMessageMiddleware
    {
        $options = $this->options($container, $this->busIdentifier);
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

    /** @param mixed[] $arguments */
    public static function __callStatic(string $name, array $arguments): HandleMessageMiddleware
    {
        $container = self::assertContainer($name, $arguments);

        return (new self($name))($container);
    }
}
