<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;
use function is_string;

/** @psalm-import-type RetryStrategyConfig from ConfigProvider */
final class RetryStrategyContainerFactory
{
    public function __invoke(ContainerInterface $container): RetryStrategyContainer
    {
        $config = Util::applicationConfig($container);
        $transports = Dot::arrayDefault('symfony.messenger.transports', $config, []);

        $retryConfig = [];
        foreach ($transports as $transportName => $transportConfig) {
            assert(is_string($transportName));
            assert(is_array($transportConfig));

            if (! isset($transportConfig['retry_strategy']) || ! is_array($transportConfig['retry_strategy'])) {
                continue;
            }

            /** @psalm-var RetryStrategyConfig $transportConfig['retry_strategy'] */

            $retryConfig[$transportName] = $transportConfig['retry_strategy'];
        }

        return new RetryStrategyContainer($container, $retryConfig);
    }
}
