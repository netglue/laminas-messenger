<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

class DefaultEventBusConfigProvider
{
    /** @return mixed[] */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->dependencies(),
            'symfony' => [
                'messenger' => [
                    'buses' => $this->busConfig(),
                ],
            ],
        ];
    }

    /** @return mixed[] */
    private function dependencies(): array
    {
        return [
            'factories' => [
                'event_bus' => [Container\MessageBusStaticFactory::class, 'event_bus'],
                'event_bus.middleware.add_bus_name_stamp' => [Container\Middleware\BusNameStampMiddlewareStaticFactory::class, 'event_bus'],
                'event_bus.middleware.send_message' => [Container\Middleware\MessageSenderMiddlewareStaticFactory::class, 'event_bus'],
                'event_bus.middleware.handle_message' => [Container\Middleware\MessageHandlerMiddlewareStaticFactory::class, 'event_bus'],
            ],
        ];
    }

    /** @return mixed[] */
    private function busConfig(): array
    {
        return [
            'event_bus' => [
                'allows_zero_handlers' => true,
                'middleware' => [
                    'event_bus.middleware.add_bus_name_stamp',
                    'event_bus.middleware.send_message',
                    'event_bus.middleware.handle_message',
                ],
                'handler_locator' => HandlerLocator\OneToManyFqcnContainerHandlerLocator::class,
                'handlers' => [],
                'routes' => [],
            ],
        ];
    }
}
