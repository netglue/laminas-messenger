<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\DefaultCommandBusConfigProvider;
use Netglue\PsrContainer\Messenger\MessageBusOptions;
use PHPUnit\Framework\TestCase;

class DefaultCommandBusConfigProviderTest extends TestCase
{
    /** @var DefaultCommandBusConfigProvider */
    private $provider;

    protected function setUp() : void
    {
        parent::setUp();
        $this->provider = new DefaultCommandBusConfigProvider();
    }

    public function testDefaultBusOptionsAreValid() : void
    {
        $config = $this->provider->__invoke();
        $this->assertIsArray($config['symfony']['messenger']['buses']);
        foreach ($config['symfony']['messenger']['buses'] as $optionArray) {
            new MessageBusOptions($optionArray);
        }
        $this->addToAssertionCount(1);
    }
}
