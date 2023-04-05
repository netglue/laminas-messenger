<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Netglue\PsrContainer\Messenger\Container\FailureReceiversProvider;
use Netglue\PsrContainer\Messenger\Container\Util;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

use function in_array;
use function sprintf;

/** @phpcs:disable SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion */
final class FailureCommandAbstractFactory
{
    private const CAN_CREATE = [
        FailedMessagesRemoveCommand::class,
        FailedMessagesShowCommand::class,
    ];

    /** @var value-of<self::CAN_CREATE> */
    private readonly string $commandName;

    /** @param class-string $commandName */
    public function __construct(string $commandName)
    {
        if (! in_array($commandName, self::CAN_CREATE, true)) {
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
            Util::getGlobalFailureTransportName($container),
            $container->get(FailureReceiversProvider::class),
        );
    }

    /**
     * @param value-of<self::CAN_CREATE> $name
     * @param mixed[]                    $arguments
     */
    public static function __callStatic(string $name, array $arguments): Command
    {
        $container = Util::assertStaticFactoryContainer($name, $arguments);

        return (new self($name))($container);
    }
}
