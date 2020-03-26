<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\StaticFactoryContainerAssertion;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;
use function in_array;
use function sprintf;

class FailureCommandAbstractFactory
{
    use StaticFactoryContainerAssertion;

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
                $commandName
            ));
        }

        $this->commandName = $commandName;
    }

    public function __invoke(ContainerInterface $container) : Command
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $transportName = $config['symfony']['messenger']['failure_transport'] ?? null;

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

        return new $this->commandName($transportName, $container->get($transportName));
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $name, array $arguments) : Command
    {
        $container = self::assertContainer($name, $arguments);

        return (new static($name))($container);
    }
}
