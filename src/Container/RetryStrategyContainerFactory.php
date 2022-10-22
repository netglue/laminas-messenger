<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use Psr\Container\ContainerInterface;

use function is_array;

class RetryStrategyContainerFactory
{
    public function __invoke(ContainerInterface $container): RetryStrategyContainer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['framework']['messenger']['transports'] ?? [];

        $retryConfig = [];
        foreach ($config as $transportName => $transportConfig) {
            if (! isset($transportConfig['retry_strategy']) || ! is_array($transportConfig['retry_strategy'])) {
                continue;
            }

            $retryConfig[$transportName] = $transportConfig['retry_strategy'];
        }

        return new RetryStrategyContainer($container, $retryConfig);
    }
}
