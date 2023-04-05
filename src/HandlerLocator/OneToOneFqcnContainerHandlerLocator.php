<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

use function assert;
use function is_callable;
use function is_string;

final class OneToOneFqcnContainerHandlerLocator implements HandlersLocatorInterface
{
    /** @param iterable<string, string|mixed> $handlers */
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
        foreach ($this->handlers as $messageName => $handlerName) {
            if (! is_string($handlerName)) {
                throw new ConfigurationError(
                    'Handler should be a string representing a single handler to retrieve from the container',
                );
            }

            if ($messageName !== $type) {
                continue;
            }

            $handler = $this->container->get($handlerName);
            assert(is_callable($handler));

            $descriptor = new HandlerDescriptor($handler);
            if (! $this->shouldHandle($envelope, $descriptor)) {
                continue;
            }

            yield $descriptor;
        }
    }

    private function shouldHandle(Envelope $envelope, HandlerDescriptor $handlerDescriptor): bool
    {
        $received = $envelope->last(ReceivedStamp::class);
        if ($received === null) {
            return true;
        }

        $expectedTransport = $handlerDescriptor->getOption('from_transport');
        if (! is_string($expectedTransport)) {
            return true;
        }

        return $received->getTransportName() === $expectedTransport;
    }
}
