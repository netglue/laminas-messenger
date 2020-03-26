<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\RoutableMessageBus;
use function array_keys;

class ConsumeCommandFactory
{
    public function __invoke(ContainerInterface $container) : ConsumeMessagesCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $logger = $config['symfony']['messenger']['logger'] ?? null;
        $receivers = $config['symfony']['messenger']['transports'] ?? [];

        if ($container->has(EventDispatcherInterface::class)) {
            $dispatcher = $container->get(EventDispatcherInterface::class);
        } else {
            $dispatcher = new EventDispatcher();
        }

        return new ConsumeMessagesCommand(
            new RoutableMessageBus($container),
            $container,
            $dispatcher,
            $logger ? $container->get($logger) : null,
            array_keys($receivers)
        );
    }
}
