<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\MessengerTest\Fixture;

class EventListenerOne
{
    public bool $triggered = false;

    public function __invoke(TestEvent $event): void
    {
        $this->triggered = true;
    }
}
