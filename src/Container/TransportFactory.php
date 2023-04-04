<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\TransportFactoryFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function is_array;
use function is_string;
use function sprintf;

final class TransportFactory
{
    use StaticFactoryContainerAssertion;

    /** @param non-empty-string $id */
    public function __construct(private string $id)
    {
    }

    public function __invoke(ContainerInterface $container): TransportInterface
    {
        $options = $this->options($container);

        $serializerName = Dot::stringOrNull('serializer', $options);
        /** @psalm-var mixed $serializer */
        $serializer = is_string($serializerName)
            ? $container->get($serializerName)
            : null;
        $serializer = $serializer instanceof SerializerInterface ? $serializer : new PhpSerializer();
        $factoryFactory = $container->get(TransportFactoryFactory::class);
        $factory = $factoryFactory($options['dsn'], $container);

        $transportOptions = Dot::arrayDefault('options', $options, []);

        return $factory->createTransport(
            $options['dsn'],
            $transportOptions,
            $serializer,
        );
    }

    /**
     * @param non-empty-string $id
     * @param mixed[]          $arguments
     */
    public static function __callStatic(string $id, array $arguments): TransportInterface
    {
        $container = self::assertContainer($id, $arguments);

        return (new self($id))($container);
    }

    /** @return array{dsn: string, ...} */
    private function options(ContainerInterface $container): array
    {
        $config = Util::applicationConfig($container);
        $options = Dot::valueOrNull(
            sprintf('symfony|messenger|transports|%s', $this->id),
            $config,
            '|',
        ) ?? [];

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
