<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="art/nova-field-indicator-logo.png">
        <img src="art/nova-field-indicator-logo-light.png" alt="NovaField Indicator" width="600">
    </picture>
</p>

# NovaField Indicator

A colour-coded status indicator for Laravel Nova — a dot, ring, square or pill that
reads a value and shows what it means, configured from the enum your application
already has.

[![Latest version](https://img.shields.io/packagist/v/gabrielesbaiz/nova-field-indicator.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/nova-field-indicator)
[![PHP](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/nova-field-indicator/php?style=flat-square)](composer.json)
[![Laravel](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/nova-field-indicator/illuminate%2Fsupport?style=flat-square&label=laravel)](composer.json)
[![Downloads](https://img.shields.io/packagist/dt/gabrielesbaiz/nova-field-indicator.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/nova-field-indicator)
[![Stars](https://img.shields.io/github/stars/gabrielesbaiz/nova-field-indicator?style=flat-square&logo=github)](https://github.com/gabrielesbaiz/nova-field-indicator/stargazers)
[![Sponsor](https://img.shields.io/github/sponsors/gabrielesbaiz?style=flat-square&label=sponsor&logo=github)](https://github.com/sponsors/gabrielesbaiz)

### 📖 [Read the documentation →](https://gabrielesbaiz.github.io/nova-field-indicator/)

Every method, every config key, and a live palette page where picking a shape,
size and shade rewrites the PHP call as you click it.

> [!CAUTION]
> **Upgrading from 2.x?** Read [UPGRADE.md](UPGRADE.md) first. On Nova 5 the
> `success`, `danger`, `warning` and `info` colours rendered an **invisible**
> dot — they now appear, so columns that looked empty will fill with colour.
> Named colours also move from the Tailwind v1 palette to Tailwind 3.
> `php artisan nova-field-indicator:upgrade --dry-run` previews the rest.

> [!IMPORTANT]
> A ⭐ costs you nothing and helps other developers find this package.
> [Sponsoring](https://github.com/sponsors/gabrielesbaiz) keeps it compatible
> with every new Laravel and Nova release.

## What it does

Nova ships `Badge`, which is good at four hard-coded states. This is for the rest:
a status column whose colours are yours, whose states come from an enum, and which
still reads correctly in dark mode and to a screen reader.

- **Enum-first.** `->enum(Status::class)` derives labels, colours and icons from one
  enum. If the attribute is already enum-cast and the enum implements the contracts,
  the field needs no configuration at all.
- **The whole palette.** Any Tailwind family and shade, light/dark pairs, or literal
  CSS colours — resolved against Nova's own `--colors-*` variables, so re-theming
  Nova re-themes every indicator.
- **Four shapes and three sizes**, plus optional heroicons that cost nothing extra
  in the bundle because they are Nova's own.
- **Continuous values** via `->ranges()`, for scores, percentages and stock levels.
- **An index filter** with `->filterable()`, built from your states or your enum.
- **Accessible by default** — colour is never the only channel, and a label-less
  mark is still a named graphic.
- **Resolved on the server**, so an index ships one small object per row instead of
  your entire configuration repeated per cell.
- **1.09 kB of JavaScript** gzipped, with no runtime dependency of its own.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Nova 5.7+ — Nova 6 is explicitly conflicted

## Installation

```bash
composer require gabrielesbaiz/nova-field-indicator
```

Optionally publish the config and translations:

```bash
php artisan vendor:publish --tag=nova-field-indicator-config
php artisan vendor:publish --tag=nova-field-indicator-lang
```

## Quick start

```php
use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;

NovaFieldIndicator::make('Status')
    ->enum(Status::class)
    ->withIcons()
    ->filterable(),
```

Everything else is on the [documentation site](https://gabrielesbaiz.github.io/nova-field-indicator/).

## Testing

```bash
composer check   # Pint + PHPStan level 6 + Pest
npm test         # Vitest
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](SECURITY.md) on how to report security
vulnerabilities.

## Credits

- [Oleg Khalin](https://github.com/oleghalin) — the original
  [nova4-indicator-field](https://github.com/oleghalin/nova4-indicator-field)
- [Gabriele Sbaiz](https://github.com/gabrielesbaiz)
- [All Contributors](../../contributors)

## Support this package

If it saves you time, consider [sponsoring](https://github.com/sponsors/gabrielesbaiz).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
