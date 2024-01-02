<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Container\Util;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\MissingDependency;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransportFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory;
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

final class TransportFactoryFactory
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
                if (! class_exists(DoctrineTransport::class)) {
                    throw MissingDependency::forTransport('doctrine', 'symfony/doctrine-messenger');
                }

                return new DoctrineTransportFactory($container);

            case 'in-memory':
                return new InMemoryTransportFactory();

            case 'redis':
                if (! class_exists(RedisTransportFactory::class)) {
                    throw MissingDependency::forTransport('redis', 'symfony/redis-messenger');
                }

                return new RedisTransportFactory();

            case 'sync':
                $messageBus = $container->get(trim($config, '/'));
                assert($messageBus instanceof MessageBusInterface);

                return new SyncTransportFactory($messageBus);
        }

        parse_str(parse_url($dsn, PHP_URL_QUERY) ?? '', $options);

        $config = Util::applicationConfig($container);
        $transportFactories = Dot::arrayDefault('symfony.messenger.transport_factories', $config, []);
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
