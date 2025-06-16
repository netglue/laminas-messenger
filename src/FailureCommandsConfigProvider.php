<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\Container\Command\FailedMessagesRetryCommandFactory;
use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
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

    /** @return ServiceManagerConfiguration */
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
                'messenger:failed:remove' => FailedMessagesRemoveCommand::class,
                'messenger:failed:retry' => FailedMessagesRetryCommand::class,
                'messenger:failed:show' => FailedMessagesShowCommand::class,
            ],
        ];
    }
}
