[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://vshymanskyy.github.io/StandWithUkraine)

# Mezzio/Laminas Factories for Symfony Messenger

![Continuous Integration](https://github.com/netglue/laminas-messenger/workflows/Continuous%20Integration/badge.svg)
[![codecov](https://codecov.io/gh/netglue/laminas-messenger/branch/main/graph/badge.svg)](https://codecov.io/gh/netglue/laminas-messenger)
[![Type Coverage](https://shepherd.dev/github/netglue/laminas-messenger/coverage.svg)](https://shepherd.dev/github/netglue/laminas-messenger)
### Introduction

This library aims to provide a way of getting Symfony Messenger up and running in a Laminas/Mezzio application with minimum fuss.

Portions of this library borrow heavily from [xtreamwayz/expressive-messenger](https://github.com/xtreamwayz/expressive-messenger)

These docs assume that you are familiar with [Symfony Messenger](https://symfony.com/doc/current/messenger.html) and the conventions associated with setting up a [Mezzio application](https://docs.mezzio.dev/mezzio/).

### Package Dependencies/Suggestions

Because Messenger provides Symfony cli commands to consume queues and process messages amongst other things, this package suggests [laminas/laminas-cli](https://github.com/laminas/laminas-cli). This Symfony CLI integration works on a convention that commands are available in your DI container configuration as a hash map under `config.laminas-cli.commands` using the command name as the key and the container identifier as the value. If that's not how you roll, then you'll still be able to benefit from the command factories, you'll just have to wire them up how you like. If you choose to install `laminas/laminas-cli`, then you'll be able to issue a `vendor/bin/laminas messenger:consume [options]` without much trouble.

If you want to use a transport not shipped by default with Symfony Messenger, such as AMQP, then you'll need to `composer require symfony/amqp-messenger` for example.

### Installation & Configuration

```bash
composer require netglue/laminas-messenger
```

During installation, you will be asked if you want to inject the main config provider `ConfigProvider::class`. This
configures the 'consume' and 'debug' cli tools and factories for retry strategies and transport factories.
Without further configuration, you still won't have any usable message buses, so there are 3 more config providers
available:

- `FailureCommandsConfigProvider::class` configures cli tools that allow you to inspect and manipulate the failure
  transport/queue and as such require that a failure transport is configured. Manually add this config provider to your
  setup if you want to configure a failure transport/queue.
  
- `DefaultCommandBusConfigProvider::class` provides a typical setup for a single command bus retrievable from the
  container with the key `command_bus`.
  
- `DefaultEventBusConfigProvider::class` provides a typical setup for an event bus retrievable with `event_bus`

None of those config providers assume anything about your transport setup so in order to get up and running, you should
also see the annotated example configurations in [`./docs`](./docs)…

## Upgrading from 1.x to 2.x

- ### SigTerm Listener
  Version 2 automatically attaches the SIGTERM listener to the consume command. This means that if you were previously doing this yourself, you should probably remove that listener.
- ### Internal helper traits removed
  If you had been writing your own factories and making use of the previously shipped traits, these are all replaced with a collection of static utility methods which are also all now marked as internal.
- ### Inheritance and general BC breaks
  If you were extending any of the shipped factories or classes or factories, you'll get runtime errors due to pretty much everything gaining the final keyword - previously, everything was marked soft `@final` so your SA tools should have warned you about that anyhow.

  There are likely many subtle BC breaks that should generally not affect you if you were using this lib with a configuration only approach _(its intended usage)_. Configuration remains largely unchanged with optional additions only.
- ### Symfony 6.x Compatibility
  Version 2 remains compatible with `symfony/messenger@^5.3` and gains compatibility with `^6` - v5 support is not likely to remain for very long though.
- ### Multiple Failure Transports
  V2 comes with support for failure transports assigned to specific receivers along with  the existing default failure transport. This means that you can have different failure queues for different message types. You can find [example config here](./docs/example-failure-transports.php).
- ### Static Analysis and Test Coverage Improvements
  Types are more refined across the board and the baseline is looking good. A number of psalm types are defined on the main [ConfigProvider](./src/ConfigProvider.php) that you might find useful for annotating your configuration structures.

_fin._
