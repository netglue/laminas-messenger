<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function is_string;
use function sprintf;

trait FailureTransportRetrievalBehaviour
{
    private function hasFailureTransport(ContainerInterface $container): bool
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $transportName = $config['symfony']['messenger']['failure_transport'] ?? null;

        return is_string($transportName) && $container->has($transportName);
    }

    private function getFailureTransport(ContainerInterface $container): TransportInterface
    {
        $transportName = $this->getFailureTransportName($container);
        if (! $container->has($transportName)) {
            throw new ConfigurationError(sprintf(
                'The transport "%s" designated as the failure transport is not present in ' .
                'the DI container',
                $transportName
            ));
        }

        return $container->get($transportName);
    }

    private function getFailureTransportName(ContainerInterface $container): string
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $transportName = $config['symfony']['messenger']['failure_transport'] ?? null;

        if (! $transportName) {
            throw new ConfigurationError('No failure transport has been specified');
        }

        return $transportName;
    }
}
