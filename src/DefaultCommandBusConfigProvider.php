<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

class DefaultCommandBusConfigProvider
{
    /** @return mixed[] */
    public function __invoke() : array
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
    private function dependencies() : array
    {
        return [
            'factories' => [
                'command_bus' => [Container\MessageBusStaticFactory::class, 'command_bus'],
                'command_bus.middleware.sender' => [Container\MessageSenderMiddlewareStaticFactory::class, 'command_bus'],
                'command_bus.middleware.handler' => [Container\MessageHandlerMiddlewareStaticFactory::class, 'command_bus'],
            ],
        ];
    }

    /** @return mixed[] */
    private function busConfig() : array
    {
        return [
            'command_bus' => [
                'allow_zero_handlers' => false,
                'middleware' => [
                    'command_bus.middleware.sender',
                    'command_bus.middleware.handler',
                ],
                'handler_locator' => HandlerLocator\OneToOneFqcnContainerHandlerLocator::class,
                'handlers' => [],
                'routes' => [],
            ],
        ];
    }
}
