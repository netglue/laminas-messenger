<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\ConfigInterface;
use Netglue\PsrContainer\Messenger\Container\Command\FailedMessagesRetryCommandFactory;
use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

use function assert;
use function is_string;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
final class FailureCommandsConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->dependencies(),
            'laminas-cli' => $this->consoleConfig(),
        ];
    }

    /** @return ServiceManagerConfigurationType */
    private function dependencies(): array
    {
        return [
            'factories' => [
                FailedMessagesRemoveCommand::class => [FailureCommandAbstractFactory::class, FailedMessagesRemoveCommand::class],
                FailedMessagesRetryCommand::class => FailedMessagesRetryCommandFactory::class,
                FailedMessagesShowCommand::class => [FailureCommandAbstractFactory::class, FailedMessagesShowCommand::class],
            ],
        ];
    }

    /** @return array<string, array<string, class-string>> */
    private function consoleConfig(): array
    {
        return [
            'commands' => [
                self::assertCommandName(FailedMessagesRemoveCommand::getDefaultName()) => FailedMessagesRemoveCommand::class,
                self::assertCommandName(FailedMessagesRetryCommand::getDefaultName()) => FailedMessagesRetryCommand::class,
                self::assertCommandName(FailedMessagesShowCommand::getDefaultName()) => FailedMessagesShowCommand::class,
            ],
        ];
    }

    private static function assertCommandName(string|null $name): string
    {
        assert(is_string($name) && $name !== '');

        return $name;
    }
}
