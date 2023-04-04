<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\Util;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\RoutableMessageBus;

final class FailedMessagesRetryCommandFactory
{
    public function __invoke(ContainerInterface $container): FailedMessagesRetryCommand
    {
        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        return new FailedMessagesRetryCommand(
            Util::getGlobalFailureTransportName($container),
            Util::getGlobalFailureTransport($container),
            new RoutableMessageBus($container),
            $dispatcher,
            Util::defaultLoggerOrNull($container),
        );
    }
}
