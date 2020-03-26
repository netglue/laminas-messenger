<?php
declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Command\DebugCommand;
use function array_combine;
use function array_keys;

class DebugCommandFactory
{
    public function __invoke(ContainerInterface $container) : DebugCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $busConfig = $config['symfony']['messenger']['buses'] ?: [];
        $map = array_combine(array_keys($busConfig), array_keys($busConfig));

        return new DebugCommand($map);
    }
}
