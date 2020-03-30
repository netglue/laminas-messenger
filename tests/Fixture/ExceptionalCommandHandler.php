<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Fixture;

use Netglue\PsrContainer\Messenger\Exception\InvalidArgument;

class ExceptionalCommandHandler
{
    public function __invoke(TestCommand $command) : void
    {
        throw new InvalidArgument('Something went wrong');
    }
}
