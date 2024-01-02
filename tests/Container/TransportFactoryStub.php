<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/** @implements TransportFactoryInterface<TransportInterface> */
final class TransportFactoryStub implements TransportFactoryInterface
{
    public SerializerInterface|null $serializer = null;
    public string|null $dsn = null;
    public array|null $options = null;

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** @inheritDoc */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $this->serializer = $serializer;
        $this->dsn = $dsn;
        $this->options = $options;

        return $this->transport;
    }

    /** @inheritDoc */
    public function supports(string $dsn, array $options): bool
    {
        return true;
    }
}
