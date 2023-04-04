<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\MessageBusOptions;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @deprecated This trait will be removed in version 2.0.0
 */
trait MessageBusOptionsRetrievalBehaviour
{
    private function options(ContainerInterface $container, string $busIdentifier): MessageBusOptions
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['symfony']['messenger']['buses'][$busIdentifier] ?? [];

        return new MessageBusOptions($config);
    }
}
