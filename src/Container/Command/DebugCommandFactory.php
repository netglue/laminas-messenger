<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Command\DebugCommand;

use function is_string;

final class DebugCommandFactory
{
    public function __invoke(ContainerInterface $container): DebugCommand
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $busList = $config['symfony']['messenger']['buses'] ?: [];
        $map = [];
        foreach ($busList as $bus => $busConfig) {
            $handlers = $busConfig['handlers'] ?: [];
            $map[$bus] = [];
            foreach ($handlers as $message => $handlerList) {
                $map[$bus][$message] = [];
                if (is_string($handlerList)) {
                    $handlerList = [$handlerList];
                }

                foreach ($handlerList as $handler) {
                    $map[$bus][$message][] = [$handler, []];
                }
            }
        }

        return new DebugCommand($map);
    }
}
