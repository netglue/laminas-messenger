<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\UnknownTransportScheme;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\AmqpExt\AmqpTransportFactory;
use Symfony\Component\Messenger\Transport\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\RedisExt\RedisTransportFactory;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Sync\SyncTransportFactory;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use function explode;
use function trim;

class TransportFactory
{
    use StaticFactoryContainerAssertion;

    /** @var string */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __invoke(ContainerInterface $container) : TransportInterface
    {
        $options = $this->options($container);
        $dsn = $options['dsn'] ?? null;

        $serializer = $options['serializer'] ?? null;
        $serializer = $serializer ? $container->get($serializer) : null;
        $serializer = $serializer ?: new PhpSerializer();

        $factory = $this->transportFactoryFromDsn($dsn, $container);

        return $factory->createTransport($dsn, $options['options'] ?? [], $serializer);
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $id, array $arguments) : TransportInterface
    {
        $container = self::assertContainer($id, $arguments);

        return (new static($id))($container);
    }

    private function transportFactoryFromDsn(string $dsn, ContainerInterface $container) : TransportFactoryInterface
    {
        [$scheme, $config] = explode(':', $dsn, 2);
        switch ($scheme) {
            case 'amqp':
                return new AmqpTransportFactory();
            case 'doctrine': // Unsupported so far
                break;
            case 'in-memory':
                return new InMemoryTransportFactory();
            case 'redis':
                return new RedisTransportFactory();
            case 'sync':
                return new SyncTransportFactory($container->get(trim($config, '/')));
        }

        throw UnknownTransportScheme::withOffendingString($scheme);
    }

    /** @return mixed[] */
    private function options(ContainerInterface $container) : array
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return $config['symfony']['messenger']['transports'][$this->id] ?? [];
    }
}
