<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

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
        $factoryFactory = $container->get(TransportFactoryFactory::class);
        $factory = $factoryFactory($dsn, $container);

        return $factory->createTransport($dsn, $options['options'] ?? [], $serializer);
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $id, array $arguments) : TransportInterface
    {
        $container = self::assertContainer($id, $arguments);

        return (new static($id))($container);
    }

    /** @return mixed[] */
    private function options(ContainerInterface $container) : array
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return $config['symfony']['messenger']['transports'][$this->id] ?? [];
    }
}
