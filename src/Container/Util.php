<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use GSteel\Dot;
use Laminas\Stdlib\ArrayUtils;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\Exception\BadMethodCall;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function is_iterable;
use function is_string;
use function sprintf;

/**
 * @internal
 *
 * @psalm-import-type TransportSetup from ConfigProvider
 * @psalm-import-type BusConfig from ConfigProvider
 */
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

    /**
     * Return the name of the globally configured failure transport or throw an exception
     *
     * @return non-empty-string
     *
     * @throws ConfigurationError If no global failure transport has been defined.
     */
    public static function getGlobalFailureTransportName(ContainerInterface $container): string
    {
        $config = self::applicationConfig($container);
        $transportName = Dot::stringOrNull('symfony.messenger.failure_transport', $config);

        if (! is_string($transportName) || $transportName === '') {
            throw new ConfigurationError('No failure transport has been specified');
        }

        return $transportName;
    }

    public static function hasGlobalFailureTransport(ContainerInterface $container): bool
    {
        try {
            self::getGlobalFailureTransportName($container);

            return true;
        } catch (ConfigurationError) {
            return false;
        }
    }

    /**
     * Used to assert a Psr\Container argument is present for factories using __callStatic()
     *
     * @param array<array-key, mixed> $arguments
     */
    public static function assertStaticFactoryContainer(string $methodName, array $arguments): ContainerInterface
    {
        $container = $arguments[0] ?? null;
        if (! $container instanceof ContainerInterface) {
            throw new BadMethodCall(sprintf(
                'The first argument to %s must be an instance of %s',
                $methodName,
                ContainerInterface::class,
            ));
        }

        return $container;
    }

    /** @param non-empty-string $busIdentifier */
    public static function messageBusOptions(ContainerInterface $container, string $busIdentifier): MessageBusOptions
    {
        $config = self::busConfiguration($container);
        $options = $config[$busIdentifier] ?? [];

        return new MessageBusOptions($options);
    }

    /** @return array<non-empty-string, TransportSetup> */
    public static function transportConfiguration(ContainerInterface $container): array
    {
        $config = self::applicationConfig($container);

        /** @var array<non-empty-string, TransportSetup> $transports */
        $transports = Dot::arrayDefault('symfony.messenger.transports', $config, []);

        return $transports;
    }

    /** @return array<non-empty-string, BusConfig> */
    public static function busConfiguration(ContainerInterface $container): array
    {
        $config = self::applicationConfig($container);

        /** @var array<non-empty-string, BusConfig> $buses */
        $buses = Dot::arrayDefault('symfony.messenger.buses', $config, []);

        return $buses;
    }
}
