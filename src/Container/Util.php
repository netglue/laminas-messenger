<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use GSteel\Dot;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function is_iterable;

/** @internal */
final class Util
{
    /**
     * Retrieves application config from the container and returns it as a regular array if it's iterable
     *
     * @return array<array-key, mixed>
     */
    public static function applicationConfig(ContainerInterface $container): array
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        assert(is_iterable($config));

        return ArrayUtils::iteratorToArray($config);
    }

    /**
     * Fetch the default configured logger service for messenger, or null
     *
     * The logger service name is expected in application config under `symfony.messenger.logger`
     */
    public static function defaultLoggerOrNull(ContainerInterface $container): LoggerInterface|null
    {
        $config = self::applicationConfig($container);
        $loggerService = Dot::stringOrNull('symfony.messenger.logger', $config);
        $logger = $loggerService ? $container->get($loggerService) : null;
        assert($logger instanceof LoggerInterface || $logger === null);

        return $logger;
    }
}
