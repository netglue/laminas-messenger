<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\MessageBusOptions;
use Psr\Container\ContainerInterface;

trait MessageBusOptionsRetrievalBehaviour
{
    private function options(ContainerInterface $container, string $busIdentifier): MessageBusOptions
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['framework']['messenger']['buses'][$busIdentifier] ?? [];

        return new MessageBusOptions($config);
    }
}
