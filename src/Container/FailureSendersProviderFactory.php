<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Psr\Container\ContainerInterface;

use function array_filter;
use function array_unique;
use function array_values;
use function in_array;

final class FailureSendersProviderFactory
{
    public function __invoke(ContainerInterface $container): FailureSendersProvider
    {
        return new FailureSendersProvider(
            $container,
            $this->listTransportsWithConfiguredFailureQueue($container),
        );
    }

    /** @return array<non-empty-string, non-empty-string> */
    private function listTransportsWithConfiguredFailureQueue(ContainerInterface $container): array
    {
        $transports = Util::transportConfiguration($container);
        $global = Util::hasGlobalFailureTransport($container)
            ? Util::getGlobalFailureTransportName($container)
            : null;

        $failureTransportList = [$global];
        foreach ($transports as $transport) {
            $failureTransportList[] = $transport['failure_transport'] ?? null;
        }

        $failureTransportList = array_values(array_unique(array_filter($failureTransportList)));

        $list = [];
        foreach ($transports as $name => $transport) {
            if (in_array($name, $failureTransportList, true)) {
                continue;
            }

            $failureTransport = $transport['failure_transport'] ?? $global;
            if ($failureTransport === null) {
                continue;
            }

            $list[$name] = $failureTransport;
        }

        return $list;
    }
}
