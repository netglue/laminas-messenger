<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\MissingDependency;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

use function assert;
use function class_exists;
use function count;
use function explode;
use function is_string;
use function parse_str;
use function parse_url;
use function sprintf;
use function trim;

use const PHP_URL_QUERY;

/** @final */
class TransportFactoryFactory
{
    public function __invoke(string $dsn, ContainerInterface $container): TransportFactoryInterface
    {
        $parts = explode(':', $dsn, 2);
        assert(count($parts) === 2);
        [$scheme, $config] = $parts;
        switch ($scheme) {
            case 'amqp':
                if (! class_exists(AmqpTransportFactory::class)) {
                    throw MissingDependency::forTransport('amqp', 'symfony/amqp-messenger');
                }

                return new AmqpTransportFactory();

            case 'doctrine':
                return new DoctrineTransportFactory($container);

            case 'in-memory':
                return new InMemoryTransportFactory();

            case 'redis':
                if (! class_exists(RedisTransportFactory::class)) {
                    throw MissingDependency::forTransport('redis', 'symfony/redis-messenger');
                }

                return new RedisTransportFactory();

            case 'sync':
                return new SyncTransportFactory($container->get(trim($config, '/')));
        }

        parse_str(parse_url($dsn, PHP_URL_QUERY) ?? '', $options);

        $config = $container->has('config') ? $container->get('config') : [];
        $transportFactories = $config['symfony']['messenger']['transport_factories'] ?? [];
        foreach ($transportFactories as $name) {
            assert(is_string($name));
            $factory = $container->get($name);
            if (! $factory instanceof TransportFactoryInterface) {
                throw new ConfigurationError(sprintf(
                    "Transport factory '%s' must implement '%s'",
                    $factory::class,
                    TransportFactoryInterface::class,
                ));
            }

            if ($factory->supports($dsn, $options)) {
                return $factory;
            }
        }

        throw UnknownTransportScheme::withOffendingString($scheme);
    }
}
