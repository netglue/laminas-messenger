<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\ServiceManager;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class DefaultCommandBusConfigProvider
{
    /** @return array<string, mixed> */
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

    /** @return ServiceManagerConfiguration */
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

    /** @return array<string, mixed> */
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
