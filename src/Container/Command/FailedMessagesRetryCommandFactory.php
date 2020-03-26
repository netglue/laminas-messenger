<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\RoutableMessageBus;
use function sprintf;

class FailedMessagesRetryCommandFactory
{
    public function __invoke(ContainerInterface $container) : FailedMessagesRetryCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $transportName = $config['symfony']['messenger']['failure_transport'] ?? null;
        $logger = $config['symfony']['messenger']['logger'] ?? null;
        $logger = $logger ? $container->get($logger) : null;

        if (! $transportName) {
            throw new ConfigurationError('No failure transport has been specified');
        }

        if (! $container->has($transportName)) {
            throw new ConfigurationError(sprintf(
                'The transport "%s" designated as the failure transport is not present in ' .
                'the DI container',
                $transportName
            ));
        }

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        return new FailedMessagesRetryCommand(
            $transportName,
            $container->get($transportName),
            new RoutableMessageBus($container),
            $dispatcher,
            $logger
        );
    }
}
