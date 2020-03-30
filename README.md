# Mezzio/Laminas Factories for Symfony Messenger

### Introduction

This library aims to provide a way of getting Symfony Messenger up and running in a Laminas/Mezzio application with minimum fuss.

Portions of this library borrow heavily from [xtreamwayz/expressive-messenger](https://github.com/xtreamwayz/expressive-messenger)

These docs assume that you are familiar with [Symfony Messenger](https://symfony.com/doc/current/messenger.html) and the conventions associated with setting up a [Mezzio application](https://docs.mezzio.dev/mezzio/).

### Package Dependencies/Suggestions

Because Messenger provides Symfony cli commands to consume queues and process messages amongst other things, this package suggests [netglue/laminas-symfony-console](https://github.com/netglue/laminas-symfony-console). This Symfony CLI integration works on a convention that commands are available in your DI container configuration as a hash map under `config.console.commands` using the command name as the key and the container identifier as the value. If that's not how you roll, then you'll still be able to benefit from the command factories, you'll just have to wire them up how you like. If you choose to install `netglue/laminas-symfony-console`, then you'll be able to issue a `vendor/bin/cli messenger:consume [options]` without much trouble. 

### Installation

```bash
composer require netglue/laminas-messenger
```

Thanks to the [laminas/laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/), during installation, you will be asked if you want to inject the shipped config providers. Currently, there are 4:

- `ConfigProvider::class` configures the 'consume' and 'debug' cli tools and factories for retry strategies and transport factories.
- `FailureCommandsConfigProvider::class` configures cli tools that allow you to inspect and manipulate the failure transport/queue and as such require that a failure transport is configured. Skip this config provider if you don't want a failure transport/queue.
- `DefaultCommandBusConfigProvider::class` provides a typical setup for a single command bus retrievable from the container with the key `command_bus`. You may want to skip this one if you want to manually setup your command bus.
- `DefaultEventBusConfigProvider::class` provides a typical setup for an event bus retrievable with `event_bus`

The Command Bus and Event Bus config providers, might be useful as a reference for configuration.

### Configuration

Please refer to some annotated configuration examples in `./docs` and/or take a look at the config providers.

_fin._
