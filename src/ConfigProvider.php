<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Symfony\Component\Messenger as SymfonyMessenger;

/**
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 * @psalm-type RetryStrategyConfig = array{
 *     service?: string|null,
 *     max_retries?: numeric|null,
 *     delay?: numeric|null,
 *     multiplier?: numeric|null,
 *     max_delay?: numeric|null,
 * }
 */
final class ConfigProvider
{
    /** @return array<string, mixed> */
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

    /** @return ServiceManagerConfigurationType */
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

    /** @return array<string, mixed> */
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

    /** @return array<string, array<string, class-string>> */
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
