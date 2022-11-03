<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\RedisExt\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

use function explode;
use function parse_str;
use function parse_url;
use function sprintf;
use function trim;

use const PHP_URL_QUERY;

class TransportFactoryFactory
{
    public function __invoke(string $dsn, ContainerInterface $container): TransportFactoryInterface
    {
        [$scheme, $config] = explode(':', $dsn, 2);
        switch ($scheme) {
            case 'amqp':
                return new AmqpTransportFactory();

            case 'doctrine':
                return new DoctrineTransportFactory($container);

            case 'in-memory':
                return new InMemoryTransportFactory();

            case 'redis':
                return new RedisTransportFactory();

            case 'sync':
                return new SyncTransportFactory($container->get(trim($config, '/')));
        }

        parse_str(parse_url($dsn, PHP_URL_QUERY) ?? '', $options);

        $config = $container->has('config') ? $container->get('config') : [];
        $transportFactories = $config['symfony']['messenger']['transport_factories'] ?? [];
        foreach ($transportFactories as $name) {
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
