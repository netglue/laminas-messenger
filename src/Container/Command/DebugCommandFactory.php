<?php

declare(strict_types=1);

namespace Netglue\PsrContainer\Messenger\Container\Command;

use GSteel\Dot;
use Netglue\PsrContainer\Messenger\Container\Util;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Command\DebugCommand;

use function assert;
use function is_array;
use function is_string;

final class DebugCommandFactory
{
    public function __invoke(ContainerInterface $container): DebugCommand
    {
        $config = Util::applicationConfig($container);
        $busList = Dot::arrayDefault('symfony.messenger.buses', $config, []);
        $map = [];
        foreach ($busList as $bus => $busConfig) {
            assert(is_string($bus));
            assert(is_array($busConfig));

            $handlers = $busConfig['handlers'] ?? [];
            assert(is_array($handlers));
            $map[$bus] = [];
            foreach ($handlers as $message => $handlerList) {
                assert(is_array($handlerList) || is_string($handlerList));

                $map[$bus][$message] = [];
                if (is_string($handlerList)) {
                    $handlerList = [$handlerList];
                }

                foreach ($handlerList as $handler) {
                    assert(is_string($handler));
                    $map[$bus][$message][] = [$handler, []];
                }
            }
        }

        return new DebugCommand($map);
    }
}
