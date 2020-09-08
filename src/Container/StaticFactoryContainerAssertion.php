<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\BadMethodCall;
use Psr\Container\ContainerInterface;

use function sprintf;

trait StaticFactoryContainerAssertion
{
    /** @param mixed[] $arguments */
    private static function assertContainer(string $methodName, array $arguments): ContainerInterface
    {
        $container = $arguments[0] ?? null;
        if (! $container instanceof ContainerInterface) {
            throw new BadMethodCall(sprintf(
                'The first argument to %s must be an instance of %s',
                $methodName,
                ContainerInterface::class
            ));
        }

        return $container;
    }
}
