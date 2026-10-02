<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PER(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('HandlerLocator', 'src/HandlerLocator')
    ->layer('Service', [
        'src/MessageBusOptions.php',
        'src/RetryStrategyContainer.php',
        'src/TransportFactoryFactory.php',
    ])
    ->layer('Config', [
        'src/ConfigProvider.php',
        'src/DefaultCommandBusConfigProvider.php',
        'src/DefaultEventBusConfigProvider.php',
        'src/FailureCommandsConfigProvider.php',
    ])
    ->layer('Container', 'src/Container', ['src/Container/Command', 'src/Container/Middleware'])
    ->layer('Command', 'src/Container/Command')
    ->layer('Middleware', 'src/Container/Middleware')
    ->ruleset([
        'Exception'      => [],
        'HandlerLocator' => ['Exception'],
        'Service'        => ['+HandlerLocator', 'Container'],
        'Container'      => ['+Service', 'Config'],
        'Command'        => ['+Container'],
        'Middleware'     => ['+Container'],
        'Config'         => ['+Command', '+Middleware'],
    ]);
