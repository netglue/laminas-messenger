<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Psr\Container\ContainerInterface;

use function array_filter;
use function array_unique;
use function array_values;

final class FailureReceiversProviderFactory
{
    public function __invoke(ContainerInterface $container): FailureReceiversProvider
    {
        return new FailureReceiversProvider(
            $container,
            $this->listFailureTransports($container),
        );
    }

    /** @return list<non-empty-string> */
    private function listFailureTransports(ContainerInterface $container): array
    {
        $transports = Util::transportConfiguration($container);
        $global = Util::hasGlobalFailureTransport($container)
            ? Util::getGlobalFailureTransportName($container)
            : null;

        $list = [$global];
        foreach ($transports as $transport) {
            $list[] = $transport['failure_transport'] ?? null;
        }

        return array_values(array_unique(array_filter($list)));
    }
}
