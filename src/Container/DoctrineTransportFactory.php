<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function sprintf;
use function strpos;

class DoctrineTransportFactory implements TransportFactoryInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        $connection = new Connection($configuration, $driverConnection);

        return new DoctrineTransport($connection, $serializer);
    }

    /** @param mixed[] $options */
    public function supports(string $dsn, array $options): bool
    {
        return strpos($dsn, 'doctrine://') === 0;
    }
}
