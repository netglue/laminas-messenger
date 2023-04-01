<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

class ServiceNotFound extends RuntimeException implements NotFoundExceptionInterface
{
    public static function withRetryStrategy(string $transportName): self
    {
        return new static(sprintf(
            'There is not a retry strategy configured for the transport "%s"',
            $transportName,
        ));
    }
}
