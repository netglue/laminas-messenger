<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Command;

use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Netglue\PsrContainer\Messenger\Container\FailureReceiversProvider;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use Netglue\PsrContainer\MessengerTest\InMemoryContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

use function call_user_func;

class FailureCommandAbstractFactoryTest extends TestCase
{
    private InMemoryContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
    }

    public function testExceptionThrownInConstructForUnknownCommandClasses(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('I cannot create commands of the type stdClass');
        new FailureCommandAbstractFactory(stdClass::class);
    }

    public function testExceptionThrownWhenFailureTransportIsNotDefined(): void
    {
        $factory = new FailureCommandAbstractFactory(FailedMessagesRemoveCommand::class);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('No failure transport has been specified');
        $factory($this->container);
    }

    /** @return array<class-string, array{0: class-string}> */
    public static function failureCommandProvider(): array
    {
        return [
            FailedMessagesRemoveCommand::class => [FailedMessagesRemoveCommand::class],
            FailedMessagesShowCommand::class => [FailedMessagesShowCommand::class],
        ];
    }

    /** @param class-string $commandClass */
    #[DataProvider('failureCommandProvider')]
    public function testCallStaticWillReturnCommandWhenConfigIsSane(string $commandClass): void
    {
        $providerContainer = new InMemoryContainer();
        $provider = new FailureReceiversProvider($providerContainer, []);

        $this->container->setService(FailureReceiversProvider::class, $provider);
        $this->container->setService('config', [
            'symfony' => [
                'messenger' => ['failure_transport' => 'failure'],
            ],
        ]);

        /** @psalm-var mixed $command */
        $command = call_user_func([
            FailureCommandAbstractFactory::class,
            $commandClass,
        ], $this->container);

        self::assertInstanceOf($commandClass, $command);
    }
}
