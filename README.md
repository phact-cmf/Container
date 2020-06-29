# Dependency Injection Container

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phact-cmf/Container/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phact-cmf/Container/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phact-cmf/Container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phact-cmf/Container/?branch=master)
[![Build Status](https://travis-ci.org/phact-cmf/Container.svg?branch=master)](https://travis-ci.org/phact-cmf/Container)

Follows PSR-11, and PSR-1, PSR-2, PSR-4.

    Inspired by [league/container](https://container.thephpleague.com/).

## Main ideas

- Creating objects, described by definitions
- Creating objects not described by definitions (to any level of nesting)
- Ability to make calls and set properties after creating an object
- Ability to make calls and set properties by class / interface (for example, "Aware" interfaces)
- Creating objects using factories
- Aliases (tags) for any service
- Ability to analyze method / constructor dependencies using reflection
- Ability to add child containers to retrieve objects not described in the current container

## Installation

```bash
composer require phact-cmf/container
```

## Requirements

- PHP >= 7.2

## Documentation

Full documentation in progress.

[Доступна полная документация на русском языке](docs/ru.md)

## License

The MIT License (MIT). [License File](LICENSE.md).

