<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\FailureTransportRetrievalBehaviour;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\RoutableMessageBus;

class FailedMessagesRetryCommandFactory
{
    use FailureTransportRetrievalBehaviour;

    public function __invoke(ContainerInterface $container) : FailedMessagesRetryCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $logger = $config['symfony']['messenger']['logger'] ?? null;
        $logger = $logger ? $container->get($logger) : null;

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        return new FailedMessagesRetryCommand(
            $this->getFailureTransportName($container),
            $this->getFailureTransport($container),
            new RoutableMessageBus($container),
            $dispatcher,
            $logger
        );
    }
}
