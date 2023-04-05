<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function sprintf;

final class ServiceNotFound extends RuntimeException implements NotFoundExceptionInterface
{
    public static function withRetryStrategy(string $transportName): self
    {
        return new self(sprintf(
            'There is not a retry strategy configured for the transport "%s"',
            $transportName,
        ));
    }

    public static function forInvalidTransport(string $name): self
    {
        return new self(sprintf(
            'A transport with the name "%s" is either not present in the DI container or is not an instance of %s',
            $name,
            TransportInterface::class,
        ));
    }
}
