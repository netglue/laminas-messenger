<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Symfony\Component\Messenger as SymfonyMessenger;

class ConfigProvider
{
    /** @return mixed[] */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->dependencies(),
            'symfony' => [
                'messenger' => [
                    'serializer' => [
                        'format' => 'json',
                        'context' => [],
                    ],
                    'buses' => [],
                    'transports' => [],
                ],
            ],
            'console' => $this->consoleConfig(),
        ];
    }

    /** @return mixed[] */
    private function dependencies() : array
    {
        return [
            'factories' => [
                SymfonyMessenger\Transport\Serialization\PhpSerializer::class => InvokableFactory::class,
                SymfonyMessenger\Transport\Serialization\Serializer::class => Container\SymfonySerializerFactory::class,
            ],
            'aliases' => [
                SymfonyMessenger\Transport\Serialization\SerializerInterface::class => SymfonyMessenger\Transport\Serialization\Serializer::class,
            ],
        ];
    }

    /** @return mixed[] */
    private function consoleConfig() : array
    {
        return [
            'commands' => [
                'messenger:consume' => SymfonyMessenger\Command\ConsumeMessagesCommand::class,
            ],
        ];
    }
}
