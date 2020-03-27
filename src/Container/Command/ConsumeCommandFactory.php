<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Transport\TransportInterface;
use function array_key_exists;
use function array_keys;

class ConsumeCommandFactory
{
    public function __invoke(ContainerInterface $container) : ConsumeMessagesCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $logger = $config['symfony']['messenger']['logger'] ?? null;
        $logger = $logger ? $container->get($logger) : null;
        $receivers = $config['symfony']['messenger']['transports'] ?? [];

        $failureTransport = $config['symfony']['messenger']['failure_transport'] ?? null;
        if ($failureTransport && array_key_exists($failureTransport, $receivers)) {
            unset($receivers[$failureTransport]);
        }

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        $this->attachRetryListener(
            $dispatcher,
            $container,
            $logger
        );

        $this->attachFailureTransportListener(
            $dispatcher,
            $this->getFailureTransport($container),
            $logger
        );

        return new ConsumeMessagesCommand(
            new RoutableMessageBus($container),
            $container,
            $dispatcher,
            $logger,
            array_keys($receivers)
        );
    }

    private function getFailureTransport(ContainerInterface $container) :? TransportInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $transportName = $config['symfony']['messenger']['failure_transport'] ?? null;

        if ($transportName && $container->has($transportName)) {
            return $container->get($transportName);
        }

        return null;
    }

    private function attachFailureTransportListener(
        EventDispatcher $dispatcher,
        ?TransportInterface $transport,
        ?LoggerInterface $logger
    ) : void {
        if (! $transport) {
            return;
        }

        $listener = new SendFailedMessageToFailureTransportListener($transport, $logger);
        $dispatcher->addSubscriber($listener);
    }

    private function attachRetryListener(
        EventDispatcher $dispatcher,
        ContainerInterface $container,
        ?LoggerInterface $logger
    ) : void {
        $listener = new SendFailedMessageForRetryListener(
            $container,
            $container->get(RetryStrategyContainer::class),
            $logger
        );

        $dispatcher->addSubscriber($listener);
    }
}
