<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

class DefaultCommandBusConfigProvider
{
    /** @return mixed[] */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->dependencies(),
            'framework' => [
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
                'command_bus' => [Container\MessageBusStaticFactory::class, 'command_bus'],
                'command_bus.middleware.add_bus_name_stamp' => [Container\Middleware\BusNameStampMiddlewareStaticFactory::class, 'command_bus'],
                'command_bus.middleware.send_message' => [Container\Middleware\MessageSenderMiddlewareStaticFactory::class, 'command_bus'],
                'command_bus.middleware.handle_message' => [Container\Middleware\MessageHandlerMiddlewareStaticFactory::class, 'command_bus'],
            ],
        ];
    }

    /** @return mixed[] */
    private function busConfig(): array
    {
        return [
            'command_bus' => [
                'allows_zero_handlers' => false,
                'middleware' => [
                    'command_bus.middleware.add_bus_name_stamp',
                    'command_bus.middleware.send_message',
                    'command_bus.middleware.handle_message',
                ],
                'handler_locator' => HandlerLocator\OneToOneFqcnContainerHandlerLocator::class,
                'handlers' => [],
                'routes' => [],
            ],
        ];
    }
}
