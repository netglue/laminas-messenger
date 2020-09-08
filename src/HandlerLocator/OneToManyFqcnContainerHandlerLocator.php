<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\HandlerLocator;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

use function get_class;
use function is_array;

class OneToManyFqcnContainerHandlerLocator implements HandlersLocatorInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var iterable|string[][] */
    private $handlers;

    /** @param string[][] $handlers */
    public function __construct(iterable $handlers, ContainerInterface $container)
    {
        $this->container = $container;
        $this->handlers = $handlers;
    }

    /** @inheritDoc */
    public function getHandlers(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();
        $type = get_class($message);
        foreach ($this->handlers as $messageName => $handlers) {
            if (! is_array($handlers)) {
                throw new ConfigurationError(
                    'Expected an array of handler identifiers to retrieve from the container'
                );
            }

            if ($messageName !== $type) {
                continue;
            }

            foreach ($handlers as $handlerName) {
                $singleLocator = new OneToOneFqcnContainerHandlerLocator(
                    [$messageName => $handlerName],
                    $this->container
                );

                yield from $singleLocator->getHandlers($envelope);
            }
        }
    }
}
