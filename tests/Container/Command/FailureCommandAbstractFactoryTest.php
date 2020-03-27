<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Container\Command;

use Netglue\PsrContainer\Messenger\Container\Command\FailureCommandAbstractFactory;
use Netglue\PsrContainer\Messenger\Exception\ConfigurationError;
use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use function call_user_func;

class FailureCommandAbstractFactoryTest extends TestCase
{
    /** @var ObjectProphecy|ContainerInterface */
    private $container;

    protected function setUp() : void
    {
        parent::setUp();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testExceptionThrownInConstructForUnknownCommandClasses() : void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('I cannot create commands of the type stdClass');
        new FailureCommandAbstractFactory(stdClass::class);
    }

    public function testExceptionThrownWhenFailureTransportIsNotDefined() : void
    {
        $this->container->get('config')->shouldBeCalled()->willReturn([]);
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $factory = new FailureCommandAbstractFactory(FailedMessagesRemoveCommand::class);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('No failure transport has been specified');
        $factory($this->container->reveal());
    }

    public function testThatTheFailureTransportMustBePresentInTheContainer() : void
    {
        $this->container->get('config')->shouldBeCalled()->willReturn([
            'symfony' => [
                'messenger' => ['failure_transport' => 'failure'],
            ],
        ]);
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $this->container->has('failure')->shouldBeCalled()->willReturn(false);
        $factory = new FailureCommandAbstractFactory(FailedMessagesRemoveCommand::class);
        $this->expectException(ConfigurationError::class);
        $this->expectExceptionMessage('The transport "failure" designated as the failure transport is not present in the DI container');
        $factory($this->container->reveal());
    }

    public function testCallStaticWillReturnCommandWhenConfigIsSane() : void
    {
        $this->container->get('config')->shouldBeCalled()->willReturn([
            'symfony' => [
                'messenger' => ['failure_transport' => 'failure'],
            ],
        ]);
        $this->container->has('config')->shouldBeCalled()->willReturn(true);
        $this->container->has('failure')->shouldBeCalled()->willReturn(true);
        $transport = new SyncTransport(new MessageBus());
        $this->container->get('failure')->shouldBeCalled()->willReturn($transport);

        $command = call_user_func([
            FailureCommandAbstractFactory::class,
            FailedMessagesRemoveCommand::class,
        ], $this->container->reveal());

        $this->assertInstanceOf(FailedMessagesRemoveCommand::class, $command);
    }
}
