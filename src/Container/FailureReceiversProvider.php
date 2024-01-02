<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_flip;
use function array_map;
use function in_array;

/** @implements ServiceProviderInterface<TransportInterface> */
final class FailureReceiversProvider implements ServiceProviderInterface
{
    /** @param list<non-empty-string> $transportNames */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $transportNames,
    ) {
    }

    public function get(string $id): TransportInterface
    {
        $transport = $this->container->get($id);
        if (! $transport instanceof TransportInterface) {
            throw ServiceNotFound::forInvalidTransport($id);
        }

        return $transport;
    }

    public function has(string $id): bool
    {
        return in_array($id, $this->transportNames, true);
    }

    /** @inheritDoc */
    public function getProvidedServices(): array
    {
        /** @psalm-suppress TooManyArguments */
        return array_map(
            static fn (): string => TransportInterface::class,
            array_flip($this->transportNames),
        );
    }
}
