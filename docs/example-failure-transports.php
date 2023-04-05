<?php

declare(strict_types=1);

use Netglue\PsrContainer\Messenger\Container\TransportFactory;

/**
 * Symfony 6 allows specifying different failure transports on a per transport basis
 *
 * If a global default failure transport is defined, this transport will be used when something specific
 * has not been defined.
 */
return [
    'symfony' => [
        'messenger' => [
            'failure_transport' => 'my_default_failure_transport',
            'transports' => [
                // 2 arbitrary transports for specific message types:
                'events_transport' => [
                    'dsn' => 'in-memory:///',
                    // Failed 'events' will be dispatched to the default failure transport `my_default_failure_transport`
                ],
                'commands_transport' => [
                    'dsn' => 'in-memory:///',
                    'failure_transport' => 'command_failures',
                    // Failed 'commands' will be dispatched to the `command_failures` transport
                ],
                 // 2 different failure transports
                'my_default_failure_transport' => [
                    'dsn' => 'in-memory:///',
                ],
                'command_failures' => [
                    'dsn' => 'in-memory:///',
                ],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            // All transports need to be listed in dependencies:
            'events_transport' => [TransportFactory::class, 'events_transport'],
            'commands_transport' => [TransportFactory::class, 'commands_transport'],
            'my_default_failure_transport' => [TransportFactory::class, 'my_default_failure_transport'],
            'command_failures' => [TransportFactory::class, 'command_failures'],
        ],
    ],
];
