<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function assert;
use function is_array;
use function is_string;
use function sprintf;

final class TransportFactory
{
    use StaticFactoryContainerAssertion;

    public function __construct(private string $id)
    {
    }

    public function __invoke(ContainerInterface $container): TransportInterface
    {
        $options = $this->options($container);

        $serializer = $options['serializer'] ?? null;
        $serializer = $serializer ? $container->get($serializer) : null;
        $serializer = $serializer ?: new PhpSerializer();
        $factoryFactory = $container->get(TransportFactoryFactory::class);
        $factory = $factoryFactory($options['dsn'], $container);

        return $factory->createTransport($options['dsn'], $options['options'] ?? [], $serializer);
    }

    /** @param mixed[] $arguments */
    public static function __callStatic(string $id, array $arguments): TransportInterface
    {
        $container = self::assertContainer($id, $arguments);

        return (new self($id))($container);
    }

    /** @return array{dsn: string}&array<array-key, mixed> */
    private function options(ContainerInterface $container): array
    {
        $config = $container->has('config') ? $container->get('config') : [];
        assert(is_array($config));

        $options = $config['symfony']['messenger']['transports'][$this->id] ?? [];
        if (is_string($options)) {
            $options = ['dsn' => $options];
        }

        if (! is_array($options) || ! isset($options['dsn']) || ! is_string($options['dsn'])) {
            throw new ConfigurationError(sprintf(
                'There is no DSN configured for the transport with name "%s"',
                $this->id,
            ));
        }

        return $options;
    }
}
