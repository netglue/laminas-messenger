<?php
declare(strict_types=1);

use Laminas\ServiceManager\Factory\InvokableFactory;
use Netglue\PsrContainer\Messenger\Container\TransportFactory;

/**
 * If you want to use a transport with not supported by Messenger, you can configure a custom transport factory
 */
return [
    'symfony' => [
        'messenger' => [
            'transport_factories' => [
                // a class implementing TransportFactoryInterface
                \My\Custom\TransportFactory::class
            ],
            'transports' => [
                'my.custom.transport' => [
                    // any configuration the custom transport needs
                    'dsn' => 'custom:/foo',
                ],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'my.custom.transport' => [TransportFactory::class, 'my.custom.transport'],
            /**
             * You must provide a factory for your custom transport factory
             */
            \My\Custom\TransportFactory::class => InvokableFactory::class,
        ],
    ],
];
