<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\Container\FailureSendersProvider;
use Netglue\PsrContainer\Messenger\Container\Util;
use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnSigtermSignalListener;
use Symfony\Component\Messenger\RoutableMessageBus;

use function array_keys;

final class ConsumeCommandFactory
{
    public function __invoke(ContainerInterface $container): ConsumeMessagesCommand
    {
        $config = Util::applicationConfig($container);
        $logger = Util::defaultLoggerOrNull($container);
        $receivers = Dot::arrayDefault('symfony.messenger.transports', $config, []);

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        // Attach Retry Listeners. Retries will only be triggered when the transport config specifies it
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener(
            $container,
            $container->get(RetryStrategyContainer::class),
            $logger,
        ));

        // Attach Failure Queue Listener. Messages will only be sent to the failure transport when one is configured
        $dispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener(
            $container->get(FailureSendersProvider::class),
            $logger,
        ));

        // Always adds a listener to gracefully shut-down workers when SIGTERM is received
        $dispatcher->addSubscriber(new StopWorkerOnSigtermSignalListener($logger));

        return new ConsumeMessagesCommand(
            new RoutableMessageBus($container),
            $container,
            $dispatcher,
            $logger,
            array_keys($receivers),
            null,
            array_keys(Util::busConfiguration($container)),
        );
    }
}
