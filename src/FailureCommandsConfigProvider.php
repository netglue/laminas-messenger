<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Netglue\PsrContainer\Messenger\Container\Command\FailedMessagesRetryCommandFactory;
use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

class FailureCommandsConfigProvider
{
    /** @return mixed[] */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->dependencies(),
            'console' => $this->consoleConfig(),
        ];
    }

    /** @return mixed[] */
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

    /** @return mixed[] */
    private function consoleConfig(): array
    {
        return [
            'commands' => [
                FailedMessagesRemoveCommand::getDefaultName() => FailedMessagesRemoveCommand::class,
                FailedMessagesRetryCommand::getDefaultName() => FailedMessagesRetryCommand::class,
                FailedMessagesShowCommand::getDefaultName() => FailedMessagesShowCommand::class,
            ],
        ];
    }
}
