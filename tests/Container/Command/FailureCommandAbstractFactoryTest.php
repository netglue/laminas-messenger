<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Command;

use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use function call_user_func;

class FailureCommandAbstractFactoryTest extends TestCase
{
    /** @var MockObject|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testExceptionThrownInConstructForUnknownCommandClasses() : void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('I cannot create commands of the type stdClass');
        new FailureCommandAbstractFactory(stdClass::class);
    }

    public function testExceptionThrownWhenFailureTransportIsNotDefined() : void
    {
        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn([]);
        $this->container
            ->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $factory = new FailureCommandAbstractFactory(FailedMessagesRemoveCommand::class);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('No failure transport has been specified');
        $factory($this->container);
    }

    public function testThatTheFailureTransportMustBePresentInTheContainer() : void
    {
        $this->container
            ->expects(self::atLeast(2))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['failure', false],
            ]);

        $this->container
            ->expects(self::atLeast(1))
            ->method('get')
            ->with('config')
            ->willReturn([
                'symfony' => [
                    'messenger' => ['failure_transport' => 'failure'],
                ],
            ]);

        $factory = new FailureCommandAbstractFactory(FailedMessagesRemoveCommand::class);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('The transport "failure" designated as the failure transport is not present in the DI container');
        $factory($this->container);
    }

    public function testCallStaticWillReturnCommandWhenConfigIsSane() : void
    {
        $transport = new SyncTransport(new MessageBus());

        $this->container
            ->expects(self::atLeast(1))
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        'symfony' => [
                            'messenger' => ['failure_transport' => 'failure'],
                        ],
                    ],
                ],
                ['failure', $transport],
            ]);

        $this->container
            ->expects(self::atLeast(2))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                ['failure', true],
            ]);

        $command = call_user_func([
            FailureCommandAbstractFactory::class,
            FailedMessagesRemoveCommand::class,
        ], $this->container);

        self::assertInstanceOf(FailedMessagesRemoveCommand::class, $command);
    }
}
