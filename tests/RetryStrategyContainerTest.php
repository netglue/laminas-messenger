<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest;

use Netglue\PsrContainer\Messenger\Exception\ServiceNotFound;
use Netglue\PsrContainer\Messenger\RetryStrategyContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;

class RetryStrategyContainerTest extends TestCase
{
    /** @var MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /** @param mixed[] $config */
    private function subject(array $config): RetryStrategyContainer
    {
        return new RetryStrategyContainer(
            $this->container,
            $config,
        );
    }

    public function testThatNoConfigIsRequiredToGetADefaultStrategy(): void
    {
        $subject = $this->subject(['name' => []]);
        self::assertTrue($subject->has('name'));
        $strategy = $subject->get('name');
        self::assertInstanceOf(MultiplierRetryStrategy::class, $strategy);
    }

    public function testThatRepeatedRetrievalWillYieldSameInstance(): void
    {
        $subject = $this->subject(['name' => []]);
        self::assertTrue($subject->has('name'));
        $strategy = $subject->get('name');
        self::assertSame($strategy, $subject->get('name'));
    }

    public function testThatCallingGetIsExceptionalWhenNotExists(): void
    {
        $subject = $this->subject([]);
        $this->expectException(ServiceNotFound::class);
        $this->expectExceptionMessage('There is not a retry strategy configured for the transport "name"');
        $subject->get('name');
    }

    public function testThatStrategyWillBeLoadedFromParentContainerWhenServiceKeyIsDefined(): void
    {
        $expect = new MultiplierRetryStrategy();
        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('special')
            ->willReturn($expect);
        $subject = $this->subject(['name' => ['service' => 'special']]);

        $result = $subject->get('name');
        self::assertSame($expect, $result);
    }

    public function testExceptionThrownWhenReferencedServiceIsNotAStrategyType(): void
    {
        $expect = new stdClass();
        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('special')
            ->willReturn($expect);
        $subject = $this->subject(['name' => ['service' => 'special']]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('The retry strategy identified by "special" for the transport "name" is not an instance of');
        $subject->get('name');
    }
}
