<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Netglue\PsrContainer\Messenger\Exception\InvalidServiceType;
use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

use function array_key_exists;
use function get_debug_type;
use function sprintf;

/** @psalm-import-type RetryStrategyConfig from ConfigProvider */
final class RetryStrategyContainer implements ContainerInterface
{
    /** @var RetryStrategyInterface[] */
    private array $strategiesIndexedByTransport = [];

    /** @param array<string, RetryStrategyConfig> $strategyConfig */
    public function __construct(
        private readonly ContainerInterface $applicationServices,
        private readonly array $strategyConfig,
    ) {
    }

    /** @inheritDoc */
    public function has($id): bool
    {
        return array_key_exists($id, $this->strategyConfig);
    }

    /** @inheritDoc */
    public function get($id): RetryStrategyInterface
    {
        if (! $this->has($id)) {
            throw ServiceNotFound::withRetryStrategy($id);
        }

        if (array_key_exists($id, $this->strategiesIndexedByTransport)) {
            return $this->strategiesIndexedByTransport[$id];
        }

        return $this->build($id);
    }

    private function build(string $id): RetryStrategyInterface
    {
        $config = $this->strategyConfig[$id];
        $serviceName = $config['service'] ?? null;
        if ($serviceName) {
            /** @psalm-var mixed $strategy */
            $strategy = $this->applicationServices->get($serviceName);
            if ($strategy instanceof RetryStrategyInterface) {
                $this->strategiesIndexedByTransport[$id] = $strategy;

                return $strategy;
            }

            throw new InvalidServiceType(sprintf(
                'The retry strategy identified by "%s" for the transport "%s" is not an instance of "%s". Received %s',
                $serviceName,
                $id,
                RetryStrategyInterface::class,
                get_debug_type($strategy),
            ));
        }

        $maxTries = $config['max_retries'] ?? 3;
        $delay = $config['delay'] ?? 1000;
        $multiplier = $config['multiplier'] ?? 2;
        $maxDelay = $config['max_delay'] ?? 0;

        $strategy = new MultiplierRetryStrategy((int) $maxTries, (int) $delay, (int) $multiplier, (int) $maxDelay);
        $this->strategiesIndexedByTransport[$id] = $strategy;

        return $strategy;
    }
}
