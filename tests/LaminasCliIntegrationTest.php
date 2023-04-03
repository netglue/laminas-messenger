<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Generator;
use Laminas\Cli\ContainerCommandLoader;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\FailureCommandsConfigProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
final class LaminasCliIntegrationTest extends TestCase
{
    private Application $cliApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        $commands = $container->get('config')['laminas-cli']['commands'] ?? [];
        $this->cliApplication = new Application();
        $this->cliApplication->setCommandLoader(new ContainerCommandLoader($container, $commands));
    }

    private static function getContainer(): ContainerInterface
    {
        $aggregator = new ConfigAggregator([
            ConfigProvider::class,
            FailureCommandsConfigProvider::class,
            new ArrayProvider([
                'symfony' => [
                    'messenger' => ['failure_transport' => 'failure'],
                ],
                'dependencies' => [
                    'services' => [
                        'failure' => new SyncTransport(new MessageBus()),
                    ],
                ],
            ]),
        ]);

        $config = $aggregator->getMergedConfig();
        /** @psalm-var ServiceManagerConfigurationType $dependencies */
        $dependencies = $config['dependencies'];
        unset($dependencies['services']['config']);
        $dependencies['services']['config'] = $config;

        return new ServiceManager($dependencies);
    }

    /** @return Generator<string, array{0: string}> */
    public static function expectedCommandNameDataProvider(): iterable
    {
        $config = self::getContainer()->get('config');
        self::assertIsArray($config);
        $commands = $config['laminas-cli']['commands'] ?? [];
        self::assertIsArray($commands);

        foreach ($commands as $commandName => $identifier) {
            self::assertIsString($commandName);
            self::assertIsString($identifier);

            yield $commandName => [$commandName];
        }
    }

    #[DataProvider('expectedCommandNameDataProvider')]
    public function testCommandsAreAvailableToTheCliApplicationWithTheDefaultConfigProviders(string $commandName): void
    {
        self::assertTrue($this->cliApplication->has($commandName));
    }
}
