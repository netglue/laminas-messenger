[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://vshymanskyy.github.io/StandWithUkraine)

# Mezzio/Laminas Factories for Symfony Messenger

![Continuous Integration](https://github.com/netglue/laminas-messenger/workflows/Continuous%20Integration/badge.svg)
[![codecov](https://codecov.io/gh/netglue/laminas-messenger/branch/main/graph/badge.svg)](https://codecov.io/gh/netglue/laminas-messenger)

### Introduction

This library aims to provide a way of getting Symfony Messenger up and running in a Laminas/Mezzio application with minimum fuss.

Portions of this library borrow heavily from [xtreamwayz/expressive-messenger](https://github.com/xtreamwayz/expressive-messenger)

These docs assume that you are familiar with [Symfony Messenger](https://symfony.com/doc/current/messenger.html) and the conventions associated with setting up a [Mezzio application](https://docs.mezzio.dev/mezzio/).

### Package Dependencies/Suggestions

Because Messenger provides Symfony cli commands to consume queues and process messages amongst other things, this package suggests [laminas/laminas-cli](https://github.com/laminas/laminas-cli). This Symfony CLI integration works on a convention that commands are available in your DI container configuration as a hash map under `config.laminas-cli.commands` using the command name as the key and the container identifier as the value. If that's not how you roll, then you'll still be able to benefit from the command factories, you'll just have to wire them up how you like. If you choose to install `laminas/laminas-cli`, then you'll be able to issue a `vendor/bin/laminas messenger:consume [options]` without much trouble. 

### Installation & Configuration

```bash
composer require netglue/laminas-messenger
```

During installation, you will be asked if you want to inject the main config provider `ConfigProvider::class`. This
configures the 'consume' and 'debug' cli tools and factories for retry strategies and transport factories.
Without further configuration, you still won't have any usable message busses, so there are 3 more config providers
available:

- `FailureCommandsConfigProvider::class` configures cli tools that allow you to inspect and manipulate the failure
  transport/queue and as such require that a failure transport is configured. Manually add this config provider to your
  setup if you want to configure a failure transport/queue.
  
- `DefaultCommandBusConfigProvider::class` provides a typical setup for a single command bus retrievable from the
  container with the key `command_bus`.
  
- `DefaultEventBusConfigProvider::class` provides a typical setup for an event bus retrievable with `event_bus`

None of those config providers assume anything about your transport setup so in order to get up and running, you should
also see the annotated example configurations in `./docs`

_fin._
