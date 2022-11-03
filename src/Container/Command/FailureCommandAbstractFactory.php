<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\FailureTransportRetrievalBehaviour;
use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

use function in_array;
use function sprintf;

/** @final */
class FailureCommandAbstractFactory
{
    use StaticFactoryContainerAssertion;
    use FailureTransportRetrievalBehaviour;

    private const CAN_CREATE = [
        FailedMessagesRemoveCommand::class,
        FailedMessagesShowCommand::class,
    ];

    /** @param value-of<self::CAN_CREATE> $commandName */
    public function __construct(private string $commandName)
    {
        if (! in_array($commandName, self::CAN_CREATE, true)) {
            throw new InvalidArgument(sprintf(
                'I cannot create commands of the type %s',
                $commandName,
            ));
        }
    }

    public function __invoke(ContainerInterface $container): Command
    {
        return new $this->commandName(
            $this->getFailureTransportName($container),
            $this->getFailureTransport($container),
        );
    }

    /**
     * @param value-of<self::CAN_CREATE> $name
     * @param mixed[]                    $arguments
     */
    public static function __callStatic(string $name, array $arguments): Command
    {
        $container = self::assertContainer($name, $arguments);

        return (new self($name))($container);
    }
}
