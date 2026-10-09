<p align="center">
    <a href="https://roadrunner.dev"><picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
        <img alt="RoadRunner" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8" style="width: 6in; display: block">
    </picture></a>
</p>

<p align="center">Symfony Lock store backed by the RoadRunner Lock plugin</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://docs.roadrunner.dev/docs/plugins/locks)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/roadrunner-server)

[![Psalm Level](https://shepherd.dev/github/roadrunner-php/symfony-lock-driver/level.svg)](https://shepherd.dev/github/roadrunner-php/symfony-lock-driver)
[![Type Coverage](https://shepherd.dev/github/roadrunner-php/symfony-lock-driver/coverage.svg)](https://shepherd.dev/github/roadrunner-php/symfony-lock-driver)
[![codecov](https://codecov.io/gh/roadrunner-php/symfony-lock-driver/branch/1.x/graph/badge.svg)](https://codecov.io/gh/roadrunner-php/symfony-lock-driver)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Froadrunner-php%2Fsymfony-lock-driver%2F1.x)](https://dashboard.stryker-mutator.io/reports/github.com/roadrunner-php/symfony-lock-driver/1.x)

</div>

<br />

This package is a bridge between the [RoadRunner Lock plugin](https://docs.roadrunner.dev/docs/plugins/locks) and the [Symfony Lock](https://symfony.com/doc/current/components/lock.html) component.
It provides a `RoadRunnerStore`, so `symfony/lock` can manage distributed locks through the RoadRunner server shared by all your workers.

## Get Started

### Installation

```bash
composer require roadrunner-php/symfony-lock-driver
```

[![PHP](https://img.shields.io/packagist/php-v/roadrunner-php/symfony-lock-driver.svg?style=flat-square&logo=php)](https://packagist.org/packages/roadrunner-php/symfony-lock-driver)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/roadrunner-php/symfony-lock-driver.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/roadrunner-php/symfony-lock-driver)
[![License](https://img.shields.io/packagist/l/roadrunner-php/symfony-lock-driver.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/roadrunner-php/symfony-lock-driver.svg?style=flat-square)](https://packagist.org/packages/roadrunner-php/symfony-lock-driver/stats)

### Configuration

The Lock plugin is driven over RPC, so the RPC plugin must be enabled in `.rr.yaml`:

```yaml
version: "3"

rpc:
  listen: tcp://127.0.0.1:6001
```

Without a `lock` section the plugin uses the in-memory backend. To share locks between several RoadRunner instances, configure the Redis backend as described in the [Lock plugin documentation](https://docs.roadrunner.dev/docs/plugins/locks).

### Usage

Create a `RoadRunnerStore` on top of the RoadRunner Lock client and pass it to the Symfony `LockFactory`:

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

$invoiceLock = $factory->createLock('invoice-42');

if ($invoiceLock->acquire()) {
    try {
        // ... critical section
    } finally {
        $invoiceLock->release();
    }
}
```

Read more about using the Symfony Lock component [here](https://symfony.com/doc/current/components/lock.html).

## Store options

`RoadRunnerStore` accepts two timing options:

| Option           | Default       | Description |
|------------------|---------------|-------------|
| `$initialTtl`    | `300.0`       | Default lock time-to-live, in seconds. When it elapses the lock is released automatically; `0` means it never expires on its own. |
| `$initialWaitTtl`| `0`           | Default time to wait for the lock to become free, in seconds. `0` is effectively **non-blocking**: the in-memory backend caps a `0` wait at `1ms` (the Redis backend makes a single attempt), so acquiring an already-held lock fails almost immediately. A positive value blocks for up to that duration. |

```php
// Wait up to 5 seconds for the lock, and hold it for at most 30 seconds.
$store = (new RoadRunnerStore($lock))->withTtl(ttl: 30.0, waitTtl: 5.0);
```

## Contributing

Contributions are welcome! If you find an issue or have a feature request, please open
an [issue](https://github.com/roadrunner-php/symfony-lock-driver/issues) or submit a pull request.

## Credits

- [gam6itko](https://github.com/gam6itko)
- [butschster](https://github.com/butschster)
