<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\FailureTransportRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\RoutableMessageBus;

use function array_keys;

class ConsumeCommandFactory
{
    use FailureTransportRetrievalBehaviour;

    public function __invoke(ContainerInterface $container): ConsumeMessagesCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $logger = $config['symfony']['messenger']['logger'] ?? null;
        $logger = $logger ? $container->get($logger) : null;
        $receivers = $config['symfony']['messenger']['transports'] ?? [];

        if ($this->hasFailureTransport($container)) {
            unset($receivers[$this->getFailureTransportName($container)]);
        }

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        // Attach Retry Listeners. Retries will only be triggered when the transport config specifies it
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener(
            $container,
            $container->get(RetryStrategyContainer::class),
            $logger
        ));

        // Attach Failure Queue Listener if a queue has been configured
        if ($this->hasFailureTransport($container)) {
            $dispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener(
                $this->getFailureTransport($container),
                $logger
            ));
        }

        return new ConsumeMessagesCommand(
            new RoutableMessageBus($container),
            $container,
            $dispatcher,
            $logger,
            array_keys($receivers)
        );
    }
}
