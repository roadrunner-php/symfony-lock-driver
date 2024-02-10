<a href="https://roadrunner.dev" target="_blank">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
    <img align="center" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8">
  </picture>
</a>

# RoadRunner Lock Integration for Symfony

[![PHP Version Require](https://poser.pugx.org/roadrunner-php/symfony-lock-driver/require/php)](https://packagist.org/packages/roadrunner-php/symfony-lock-driver)
[![Latest Stable Version](https://poser.pugx.org/roadrunner-php/symfony-lock-driver/v/stable)](https://packagist.org/packages/roadrunner-php/symfony-lock-driver)
[![phpunit](https://github.com/roadrunner-php/symfony-lock-driver/actions/workflows/phpunit.yml/badge.svg)](https://github.com/roadrunner-php/symfony-lock-driver/actions)
[![psalm](https://github.com/roadrunner-php/symfony-lock-driver/actions/workflows/psalm.yml/badge.svg)](https://github.com/roadrunner-php/symfony-lock-driver/actions)
[![Codecov](https://codecov.io/gh/roadrunner-php/symfony-lock-driver/branch/master/graph/badge.svg)](https://codecov.io/gh/roadrunner-php/symfony-lock-driver/)
[![Total Downloads](https://poser.pugx.org/roadrunner-php/symfony-lock-driver/downloads)](https://packagist.org/roadrunner-php/symfony-lock-driver/phpunit)
<a href="https://discord.gg/8bZsjYhVVk"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

This package is a bridge that connects the powerful RoadRunner Lock plugin with the Symfony Lock component. It's
designed to help you easily manage distributed locks in your PHP applications, particularly when you're working with
high-traffic web applications and microservices.

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+

## Installation

You can install the package via composer:

```bash
composer require roadrunner-php/symfony-lock-driver

```

## Usage

Using the RoadRunner Lock with Symfony is straightforward. Here's a simple example:

```php
use RoadRunner\Lock\Lock;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Symfony\Lock\RoadRunnerStore;
use Symfony\Component\Lock\LockFactory;

require __DIR__ . '/vendor/autoload.php';

$lock = new Lock(RPC::create('tcp://127.0.0.1:6001'));
$factory = new LockFactory(
    new RoadRunnerStore($lock)
);
```

Read more about using Symfony Lock component [here](https://symfony.com/doc/current/components/lock.html).

## Contributing

Contributions are welcome! If you find an issue or have a feature request, please open
an [issue](https://github.com/roadrunner-php/issues) or submit a pull request.

## Credits

- [gam6itko](https://github.com/gam6itko)
- [butschster](https://github.com/butschster)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
