<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class SymfonySerializerFactory
{
    public function __invoke(ContainerInterface $container) : Serializer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['symfony']['messenger']['serializer'] ?? [];
        $format = $config['format'] ?? null;

        return new Serializer(null, $format, $config['context'] ?? []);
    }
}
