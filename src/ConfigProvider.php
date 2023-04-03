<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Symfony\Component\Messenger as SymfonyMessenger;

final class ConfigProvider
{
    /** @return mixed[] */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->dependencies(),
            'symfony' => [
                'messenger' => $this->messengerConfig(),
            ],
            'laminas-cli' => $this->consoleConfig(),
        ];
    }

    /** @return mixed[] */
    private function dependencies(): array
    {
        return [
            'factories' => [
                SymfonyMessenger\Command\ConsumeMessagesCommand::class => Container\Command\ConsumeCommandFactory::class,
                SymfonyMessenger\Command\DebugCommand::class => Container\Command\DebugCommandFactory::class,
                RetryStrategyContainer::class => Container\RetryStrategyContainerFactory::class,
                TransportFactoryFactory::class => InvokableFactory::class,
            ],
        ];
    }

    /** @return mixed[] */
    private function messengerConfig(): array
    {
        return [
            // This logger is used by the console commands:
            'logger' => null,
            // The name of the failure transport should be retrievable by name from the container:
            'failure_transport' => null, //'failed',
            'buses' => [],
            'transports' => [],
        ];
    }

    /** @return mixed[] */
    private function consoleConfig(): array
    {
        return [
            'commands' => [
                'messenger:consume' => SymfonyMessenger\Command\ConsumeMessagesCommand::class,
                'debug:messenger' => SymfonyMessenger\Command\DebugCommand::class,
            ],
        ];
    }
}
