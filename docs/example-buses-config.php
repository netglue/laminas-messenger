<?php // phpcs:ignoreFile
declare(strict_types=1);

use Netglue\PsrContainer\Messenger\Container\MessageBusStaticFactory;
use Netglue\PsrContainer\Messenger\Container\MessageHandlerMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\MessageSenderMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToManyFqcnContainerHandlerLocator;

return [
    'dependencies' => [
        'factories' => [
            'my.command.bus' => [MessageBusStaticFactory::class, 'my.command.bus'],
            'my.command.bus.sender-middleware' => [MessageSenderMiddlewareStaticFactory::class, 'my.command.bus'],
            'my.command.bus.handler-middleware' => [MessageHandlerMiddlewareStaticFactory::class, 'my.command.bus'],
        ],
    ],
    'symfony' => [
        'messenger' => [
            'buses' => [
                'my.command.bus' => [
                    'allows_zero_handlers' => false, // Means that it's an error if no handlers are defined for a given message

                    /**
                     * Each bus needs middleware to do anything useful.
                     *
                     * Below is a minimal configuration to handle messages
                     */
                    'middleware' => [
                        // … Middleware that inspects the message before it has been sent to a transport would go here.
                        'my.command.bus.sender-middleware', // Sends messages via a transport if configured.
                        'my.command.bus.handler-middleware', // Executes the handlers configured for the message
                    ],

                    /**
                     * Map messages to one or more handlers:
                     *
                     * Two locators are shipped, 1 message type to 1 handler and 1 message type to many handlers.
                     * Both locators operate on the basis that handlers are available in the container.
                     *
                     */
                    'handler_locator' => OneToManyFqcnContainerHandlerLocator::class,
                    'handlers' => [
                        // Example using OneToManyFqcnContainerHandlerLocator:
                        // \My\Event\SomethingHappened::class => [\My\ReactOnce::class, \My\ReactTwice::class],

                        // Example using OneToOneFqcnContainerHandlerLocator
                        // \My\Command\DoSomething::class => \My\Handler\HandleSomething::class,
                    ],

                    /**
                     * Routes define which transport(s) that messages dispatched on this bus should be sent with.
                     *
                     * The * wildcard applies to all messages.
                     * The transport for each route must be an array of one or more transport identifiers. Each transport
                     * is retrieved from the DI container by this value.
                     *
                     * An empty routes definition would mean that messages would be handled immediately and synchronously,
                     * i.e. no transport would be used.
                     *
                     * Route specific messages to specific transports by using the message name as the key.
                     */
                    'routes' => [
                        '*' => ['some.transport.identifier'],
                    ],
                ],
            ],
        ],
    ],
];
