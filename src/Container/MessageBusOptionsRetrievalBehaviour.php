<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\MessageBusOptions;
use Psr\Container\ContainerInterface;

trait MessageBusOptionsRetrievalBehaviour
{
    /** @param non-empty-string $busIdentifier */
    private function options(ContainerInterface $container, string $busIdentifier): MessageBusOptions
    {
        $config = Util::applicationConfig($container);
        $config = $config['symfony']['messenger']['buses'][$busIdentifier] ?? [];

        return new MessageBusOptions($config);
    }
}
