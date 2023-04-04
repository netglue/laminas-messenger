<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\Stdlib\AbstractOptions;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Traversable;

use function is_a;
use function iterator_to_array;
use function sprintf;

final class MessageBusOptions extends AbstractOptions
{
    /** @var string[] */
    private array $middleware = [];
    /** @var array<string, list<string>> */
    private array $handlers = [];
    /** @var array<string, list<string>> */
    private array $routes = [];
    private string|null $logger = null;
    private bool $allowsZeroHandlers = false;
    /** @var class-string */
    private string $handlerLocator = OneToManyFqcnContainerHandlerLocator::class;

    /** @param string[] $middleware */
    public function setMiddleware(iterable $middleware): void
    {
        $this->middleware = $middleware;
    }

    /** @return string[] */
    public function middleware(): iterable
    {
        return $this->middleware;
    }

    /** @param iterable<string, list<string>> $handlers */
    public function setHandlers(iterable $handlers): void
    {
        $this->handlers = $this->iterableToArray($handlers);
    }

    /** @return string[][] */
    public function handlers(): iterable
    {
        return $this->handlers;
    }

    public function setHandlerLocator(string $handlerLocator): void
    {
        if (! is_a($handlerLocator, HandlersLocatorInterface::class, true)) {
            throw new InvalidArgument(sprintf(
                'Handler locators must implement %s',
                HandlersLocatorInterface::class,
            ));
        }

        $this->handlerLocator = $handlerLocator;
    }

    public function handlerLocator(): string
    {
        return $this->handlerLocator;
    }

    /** @param iterable<string, list<string>> $routes */
    public function setRoutes(iterable $routes): void
    {
        $this->routes = $this->iterableToArray($routes);
    }

    /** @return array<string, list<string>> */
    public function routes(): iterable
    {
        return $this->routes;
    }

    public function setLogger(string $loggerId): void
    {
        $this->logger = $loggerId;
    }

    public function logger(): string|null
    {
        return $this->logger;
    }

    public function setAllowsZeroHandlers(bool $flag): void
    {
        $this->allowsZeroHandlers = $flag;
    }

    public function allowsZeroHandlers(): bool
    {
        return $this->allowsZeroHandlers;
    }

    /**
     * @param iterable<TKey, TValue> $data
     *
     * @return array<TKey, TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    private function iterableToArray(iterable $data): array
    {
        if ($data instanceof Traversable) {
            return iterator_to_array($data, true);
        }

        return $data;
    }
}
