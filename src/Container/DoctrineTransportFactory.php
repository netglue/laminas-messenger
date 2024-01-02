<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function assert;
use function sprintf;
use function strpos;

/**
 * A transport factory for doctrine connections
 *
 * The purpose of this factory is to circumvent the factory shipped with `symfony/doctrine-messenger` - The symfony
 * factory requires a ConnectionRegistry to its constructor which, AFAIK, is not generally available.
 *
 * This factory attempts to locate a DBAL connection from the DSN providing the RHS of the DSN is something retrievable
 * from the container, for example: "doctrine://my_dbal_connection" - an entity manager is also acceptable, for
 * example "doctrine://orm_default".
 *
 * For other options, consult the symfony docs at https://symfony.com/doc/current/messenger.html#doctrine-transport
 *
 * @implements TransportFactoryInterface<TransportInterface>
 */
final class DoctrineTransportFactory implements TransportFactoryInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /** @param mixed[] $options */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $configuration = Connection::buildConfiguration($dsn, $options);

        try {
            $driverConnection = $this->container->get($configuration['connection']);
            if ($driverConnection instanceof EntityManager) {
                $driverConnection = $driverConnection->getConnection();
            }
        } catch (InvalidArgumentException $e) {
            throw new TransportException(
                sprintf('Could not find Doctrine connection from Messenger DSN "%s".', $dsn),
                0,
                $e,
            );
        }

        assert($driverConnection instanceof DBALConnection);

        $connection = new Connection($configuration, $driverConnection);

        return new DoctrineTransport($connection, $serializer);
    }

    /** @param mixed[] $options */
    public function supports(string $dsn, array $options): bool
    {
        return strpos($dsn, 'doctrine://') === 0;
    }
}
