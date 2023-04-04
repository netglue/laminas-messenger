<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

use function assert;
use function is_array;
use function is_string;

final class OneToManyFqcnContainerHandlerLocator implements HandlersLocatorInterface
{
    /** @param iterable<string, list<string>|mixed> $handlers */
    public function __construct(
        private readonly iterable $handlers,
        private readonly ContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function getHandlers(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();
        $type = $message::class;
        foreach ($this->handlers as $messageName => $handlers) {
            if (! is_array($handlers)) {
                throw new ConfigurationError(
                    'Expected an array of handler identifiers to retrieve from the container',
                );
            }

            if ($messageName !== $type) {
                continue;
            }

            /** @psalm-var mixed $handlerName */
            foreach ($handlers as $handlerName) {
                assert(is_string($handlerName));
                $singleLocator = new OneToOneFqcnContainerHandlerLocator(
                    [$messageName => $handlerName],
                    $this->container,
                );

                yield from $singleLocator->getHandlers($envelope);
            }
        }
    }
}
