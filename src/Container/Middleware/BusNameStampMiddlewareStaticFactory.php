<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Middleware;

use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;

class BusNameStampMiddlewareStaticFactory
{
    use StaticFactoryContainerAssertion;

    /** @var string */
    private $busIdentifier;

    public function __construct(string $busIdentifier)
    {
        $this->busIdentifier = $busIdentifier;
    }

    public function __invoke(ContainerInterface $container) : AddBusNameStampMiddleware
    {
        return new AddBusNameStampMiddleware($this->busIdentifier);
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $name, array $arguments) : AddBusNameStampMiddleware
    {
        $container = self::assertContainer($name, $arguments);

        return (new static($name))($container);
    }
}
