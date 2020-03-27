<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Netglue\PsrContainer\Messenger\Container\DoctrineTransportFactory;
use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\RedisExt\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use function explode;
use function trim;

class TransportFactoryFactory
{
    public function __invoke(string $dsn, ContainerInterface $container) : TransportFactoryInterface
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

        throw UnknownTransportScheme::withOffendingString($scheme);
    }
}
