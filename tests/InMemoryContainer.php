<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Psr\Container\ContainerInterface;

final class InMemoryContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services = [];

    public function setService(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    /** @inheritDoc */
    public function get(string $id)
    {
        if (! isset($this->services[$id])) {
            throw new ServiceNotFound('Service ' . $id . ' not found');
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
