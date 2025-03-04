# NovaFieldIndicator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gabrielesbaiz/nova-field-indicator.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/nova-field-indicator)
[![Total Downloads](https://img.shields.io/packagist/dt/gabrielesbaiz/nova-field-indicator.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/nova-field-indicator)

A colour-coded indicator field for Laravel Nova.

Original code from [oleghalin/nova4-indicator-field](https://github.com/oleghalin/nova4-indicator-field)

## Features

- ✅ With labels
- ✅ Without labels
- ✅ Hide defined values
- ✅ Named colours
- ✅ Literal colours

## Installation

You can install the package via composer:

```bash
composer require gabrielesbaiz/nova-field-indicator
```

## Usage

```php
use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;

NovaFieldIndicator::make('Status'),
```

### Options
#### Labels

```php
NovaFieldIndicator::make('Status')
    ->labels([
        'banned' => 'Banned',
        'active' => 'Active',
        'invited' => 'Invited',
        'inactive' => 'Inactive',
    ])
```

#### Without Labels

```php
NovaFieldIndicator::make('Status')
    ->withoutLabels()
```

#### Unknown Label

```php
NovaFieldIndicator::make('Status')
    ->unknown('Unknown')
```
#### Should Hide

```php
NovaFieldIndicator::make('Status')
    ->shouldHide('active')
```

```php
NovaFieldIndicator::make('Status')
    ->shouldHide(['invited', 'requested'])
```

```php
NovaFieldIndicator::make('Status')
    ->shouldHide(function($value) {
        return $value == 'inactive';
    })
```

#### Should Hide If No

```php
NovaFieldIndicator::make('Status')
    ->shouldHideIfNo()
```

#### Colours
##### Named Colours

```php
NovaFieldIndicator::make('Status')
    ->colors([
        'banned' => 'red',
        'active' => 'green',
        'invited' => 'blue',
        'inactive' => 'grey',
    ])
```

The available colours are the default "base" colours from [Tailwind](https://tailwindcss.com/docs/colors), with the addition of black:
* 'black'   `#22292F`
* 'grey' or 'gray' `#B8C2CC`
* 'red'     `#E3342F`
* 'orange'  `#F6993F`
* 'yellow'  `#FFED4A`
* 'green'   `#38C172`
* 'teal'    `#4DC0B5`
* 'blue'    `#3490DC`
* 'indigo'  `#6574CD`
* 'purple'  `#9561E2`
* 'pink'    `#F66D9B`

As well as the following Nova variable colours:

* 'success' `var(--success)`
* 'warning' `var(--warning)`
* 'danger'  `var(--danger)`
* 'info'    `var(--info)`

Colour classes are not validated against the lists above, so if you enter an invalid colour, it will fall back to grey.

##### Literal Colours

```php
NovaFieldIndicator::make('Status')
    ->colors([
        '...' => '#ff0000',
        '...' => 'rgb(0, 255, 0)',
        '...' => 'rgba(0, 0, 0, 0.5)',
        '...' => 'hsl(120, 100%, 50%)',
        '...' => 'hsla(120, 100%, 50%, 0.5)',
        '...' => 'var(--success)',
    ])
```

##### Additional Colour Classes

```css
.indicator-yourcolourname {
    background: #000000;
}
```

```php
NovaFieldIndicator::make('Status')
    ->colors([
        'yourstatus' => 'yourcolourname',
    ])
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Oleg Khalin](https://github.com/oleghalin)
- [Gabriele Sbaiz](https://github.com/gabrielesbaiz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
