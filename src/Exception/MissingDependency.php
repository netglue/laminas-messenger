<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Exception;

use RuntimeException;

use function sprintf;

final class MissingDependency extends RuntimeException
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $package
     */
    public static function forTransport(string $name, string $package): self
    {
        return new self(sprintf(
            'Transports of type "%s" require that the composer package "%s" is installed.',
            $name,
            $package,
        ));
    }
}
