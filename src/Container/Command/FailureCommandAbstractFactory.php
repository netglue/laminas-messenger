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

class FailureCommandAbstractFactory
{
    use StaticFactoryContainerAssertion;
    use FailureTransportRetrievalBehaviour;

    /** @var string[] */
    private static $canCreate = [
        FailedMessagesRemoveCommand::class,
        FailedMessagesShowCommand::class,
    ];

    /** @var string */
    private $commandName;

    public function __construct(string $commandName)
    {
        if (! in_array($commandName, self::$canCreate, true)) {
            throw new InvalidArgument(sprintf(
                'I cannot create commands of the type %s',
                $commandName,
            ));
        }

        $this->commandName = $commandName;
    }

    public function __invoke(ContainerInterface $container): Command
    {
        return new $this->commandName(
            $this->getFailureTransportName($container),
            $this->getFailureTransport($container),
        );
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $name, array $arguments): Command
    {
        $container = self::assertContainer($name, $arguments);

        return (new static($name))($container);
    }
}
