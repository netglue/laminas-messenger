<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Exception;

use Netglue\PsrContainer\Messenger\Exception\MissingDependency;
use PHPUnit\Framework\TestCase;

class MissingDependencyTest extends TestCase
{
    public function testThatTheTransportAndPackageArePresentForTransportRelatedErrorMessages(): void
    {
        $e = MissingDependency::forTransport('some-transport', 'some-package');
        self::assertStringContainsString('"some-transport"', $e->getMessage());
        self::assertStringContainsString('"some-package"', $e->getMessage());
    }
}
