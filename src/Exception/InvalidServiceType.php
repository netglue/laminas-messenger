<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class InvalidServiceType extends RuntimeException implements ContainerExceptionInterface
{
}
