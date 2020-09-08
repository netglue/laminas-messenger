<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Exception;

use function sprintf;

class UnknownTransportScheme extends InvalidArgument
{
    public static function withOffendingString(string $scheme): self
    {
        return new static(sprintf(
            'The scheme/prefix "%s" is not a known type of transport, or one that this library cannot handle',
            $scheme
        ));
    }
}
