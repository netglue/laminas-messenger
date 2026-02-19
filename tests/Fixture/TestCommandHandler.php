<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Fixture;

final class TestCommandHandler
{
    public function __invoke(TestCommand $command): void
    {
    }
}
