<?php
declare(strict_types=1);

use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as SymfonySerializer;

/**
 * The serializer used in all configuration below should be retrievable from the container.
 *
 * You can use:
 *
 * @link \Symfony\Component\Messenger\Transport\Serialization\SerializerInterface::class
 *
 * which is aliased by default to a symfony serializer that will serialize to JSON format.
 */

$transports = [
    // AMQP
    'my.amqp.transport' => [
        'dsn' => 'amqp://guest:guest@localhost:5672/%2f/messages',
        'serializer' => SymfonySerializer::class,
        // AMQP Connection Options
        // @link https://github.com/symfony/symfony/blob/5.0/src/Symfony/Component/Messenger/Transport/AmqpExt/Connection.php
        'options' => [],
    ],
    // Doctrine…

    // In Memory for Testing
    'my.in-memory.transport' => [
        'dsn' => 'in-memory://',
        'options' => [],
        'serializer' => SymfonySerializer::class,
    ],

    // Redis
    // @link https://symfony.com/doc/current/messenger.html#redis-transport
    'my.redis.transport' => [
        'dsn' => 'redis://localhost:6379/messages',
        'options' => [], // Redis specific options
        'serializer' => SymfonySerializer::class,
    ],

    // Synchronous
    // The rhs of the dsn will be used to fetch the correct bus from the container
    'my.sync.transport' => [
        'dsn' => 'sync://message-bus-container-identifier',
        'options' => [],
        'serializer' => SymfonySerializer::class,
    ],
];

return [
    'framework' => [
        'messenger' => [
            'transports' => $transports,
        ],
    ],
    'dependencies' => [
        'factories' => [
            /**
             * The transport must be identified in the DI Container with an alias of your choosing.
             *
             * The transport factory requires the alias so that it can look up the configuration for this
             * specific transport as defined in symfony.messenger.transports[]
             */
            'my.redis.transport' => [TransportFactory::class, 'my.redis.transport'],
        ],
    ],
];
