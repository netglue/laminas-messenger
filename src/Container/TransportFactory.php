<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function is_string;
use function sprintf;

class TransportFactory
{
    use StaticFactoryContainerAssertion;

    /** @var string */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __invoke(ContainerInterface $container): TransportInterface
    {
        $options = $this->options($container);
        $options = is_string($options) ? ['dsn' => $options] : $options;
        $dsn = $options['dsn'] ?? null;

        if (! $dsn) {
            throw new ConfigurationError(sprintf(
                'There is no DSN configured for the transport with name "%s"',
                $this->id
            ));
        }

        $serializer = $options['serializer'] ?? null;
        $serializer = $serializer ? $container->get($serializer) : null;
        $serializer = $serializer ?: new PhpSerializer();
        $factoryFactory = $container->get(TransportFactoryFactory::class);
        $factory = $factoryFactory($dsn, $container);

        return $factory->createTransport($dsn, $options['options'] ?? [], $serializer);
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $id, array $arguments): TransportInterface
    {
        $container = self::assertContainer($id, $arguments);

        return (new static($id))($container);
    }

    /** @return mixed[]|string */
    private function options(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return $config['framework']['messenger']['transports'][$this->id] ?? [];
    }
}
