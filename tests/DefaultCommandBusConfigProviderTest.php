<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\DefaultCommandBusConfigProvider;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\TestCase;

class DefaultCommandBusConfigProviderTest extends TestCase
{
    private DefaultCommandBusConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new DefaultCommandBusConfigProvider();
    }

    public function testDefaultBusOptionsAreValid(): void
    {
        $config = $this->provider->__invoke();
        self::assertArrayHasKey('symfony', $config);
        self::assertIsArray($config['symfony']);
        self::assertArrayHasKey('messenger', $config['symfony']);
        self::assertIsArray($config['symfony']['messenger']);
        self::assertArrayHasKey('buses', $config['symfony']['messenger']);
        self::assertIsArray($config['symfony']['messenger']['buses']);
        foreach ($config['symfony']['messenger']['buses'] as $optionArray) {
            self::assertIsArray($optionArray);
            new MessageBusOptions($optionArray);
        }
    }
}
