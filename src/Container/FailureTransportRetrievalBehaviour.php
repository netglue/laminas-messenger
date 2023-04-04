<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function is_string;
use function sprintf;

trait FailureTransportRetrievalBehaviour
{
    private function hasFailureTransport(ContainerInterface $container): bool
    {
        try {
            $this->getFailureTransportName($container);

            return true;
        } catch (ConfigurationError) {
            return false;
        }
    }

    private function getFailureTransport(ContainerInterface $container): TransportInterface
    {
        $transportName = $this->getFailureTransportName($container);
        if (! $container->has($transportName)) {
            throw new ConfigurationError(sprintf(
                'The transport "%s" designated as the failure transport is not present in ' .
                'the DI container',
                $transportName,
            ));
        }

        return $container->get($transportName);
    }

    /** @return non-empty-string */
    private function getFailureTransportName(ContainerInterface $container): string
    {
        $config = Util::applicationConfig($container);
        $transportName = Dot::stringOrNull('symfony.messenger.failure_transport', $config);

        if (! is_string($transportName) || $transportName === '') {
            throw new ConfigurationError('No failure transport has been specified');
        }

        return $transportName;
    }
}
