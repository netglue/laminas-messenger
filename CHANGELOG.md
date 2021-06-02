# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.0.3 - 2021-06-02

### Added

- Nothing.

### Changed

- Adds symfony/dependency-injection as a dependency due to symfony/messenger requiring it but not declaring it as a direct dependency. This effectively fixed compatibility with messenger 5.3.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2021-04-20

### Added

- Nothing.

### Changed

- Relax psr/container constraint to allow 1.0 || 2.0.
- Changed CI to use the [laminas continuous integration matrix](https://github.com/laminas/laminas-continuous-integration-action).
- 

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2020-11-02

### Added

- Nothing.

### Changed

- Removed the _optional_ config providers from `composer.json` because by default they get injected automatically when
  installed in an unattended way, for example in CI. This meant that the static factories in your own configuration
  would get screwed up during configuration merging.
  
- Updated the Readme to reflect this change in installation behaviour

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.0 - 2020-09-08

### Added

- Nothing.

### Changed

- [#4](https://github.com/netglue/laminas-messenger/pull/4) changes configuration of the shipped commands so that they
 reside under the key `laminas-cli` instead of `console` thereby integrating with the [laminas-cli](https://github.com/laminas/laminas-cli) package instead of the abandoned [netglue/laminas-symfony-console](https://github.com/netglue/laminas-symfony-console) package.

- [#1](https://github.com/netglue/laminas-messenger/pull/1) Tests have been refactored to be compatible with PHPUnit 9+ by removing usage of Prophecy.

- Upgraded Doctrine Coding Standard to 8.x and removed most customisations in `phpcs.xml`

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.0 - 2020-03-30

### Added

- Everything

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
