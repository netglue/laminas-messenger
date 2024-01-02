<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_key_exists;
use function array_map;

/**
 * Maps receivers to failure transports
 *
 * @implements ServiceProviderInterface<TransportInterface>
 */
final class FailureSendersProvider implements ServiceProviderInterface
{
    /** @param array<non-empty-string, non-empty-string> $transportMap */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $transportMap,
    ) {
    }

    public function get(string $id): TransportInterface
    {
        $failureTransport = $this->transportMap[$id] ?? null;
        if (! $failureTransport || ! $this->has($id)) {
            throw ServiceNotFound::forInvalidTransport($failureTransport ?? '[null]');
        }

        $transport = $this->container->get($failureTransport);
        if (! $transport instanceof TransportInterface) {
            throw ServiceNotFound::forInvalidTransport($failureTransport);
        }

        return $transport;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->transportMap);
    }

    /** @inheritDoc */
    public function getProvidedServices(): array
    {
        /** @psalm-suppress TooManyArguments */
        return array_map(
            static fn (): string => TransportInterface::class,
            $this->transportMap,
        );
    }
}
