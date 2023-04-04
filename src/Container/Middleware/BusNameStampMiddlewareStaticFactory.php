<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;

final class BusNameStampMiddlewareStaticFactory
{
    use StaticFactoryContainerAssertion;

    /** @param non-empty-string $busIdentifier */
    public function __construct(private string $busIdentifier)
    {
    }

    public function __invoke(ContainerInterface $container): AddBusNameStampMiddleware
    {
        return new AddBusNameStampMiddleware($this->busIdentifier);
    }

    /**
     * @param non-empty-string $name
     * @param mixed[]          $arguments
     */
    public static function __callStatic(string $name, array $arguments): AddBusNameStampMiddleware
    {
        $container = self::assertContainer($name, $arguments);

        return (new self($name))($container);
    }
}
