<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Generator;
use Laminas\Cli\ContainerCommandLoader;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use Netglue\PsrContainer\Messenger\ConfigProvider;
use Netglue\PsrContainer\Messenger\FailureCommandsConfigProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;

use function array_keys;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class LaminasCliIntegrationTest extends TestCase
{
    private Application $cliApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        $config = $container->get('config');
        self::assertIsArray($config);
        $commands = $config['laminas-cli']['commands'] ?? [];
        self::assertIsArray($commands);
        self::assertContainsOnly('string', $commands);
        self::assertContainsOnly('string', array_keys($commands));
        /** @psalm-var array<string, string> $commands */

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
        /** @psalm-var ServiceManagerConfiguration $dependencies */
        $dependencies = $config['dependencies'];
        $dependencies['services'] ??= [];
        $dependencies['services']['config'] = $config;
        /** @psalm-var ServiceManagerConfiguration $dependencies */

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
