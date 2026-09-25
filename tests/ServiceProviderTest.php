<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\Console\UpgradeCommand;

it('merges the package config', function () {
    expect(config('nova-field-indicator.appearance.shape'))->toBe('dot')
        ->and(config('nova-field-indicator.colors.default'))->toBe('gray')
        ->and(config('nova-field-indicator.colors.shades'))->toBe(['light' => 500, 'dark' => 400]);
});

/*
 * Two registrations are needed, and only one of them is what the sibling
 * packages do. Nova::translations() feeds the JavaScript translator; labels
 * here are resolved in PHP, so the framework translator has to know about the
 * same files or boolean() and unknown() pass through untranslated.
 */
it('loads json translations into the PHP translator', function () {
    app()->setLocale('it');

    expect(__('Yes'))->toBe('Sì')
        ->and(__('No'))->toBe('No')
        ->and(__('Unknown'))->toBe('Sconosciuto');
});

it('leaves unknown keys untouched, so it is a no-op untranslated', function () {
    app()->setLocale('en');

    expect(__('Some label nobody translated'))->toBe('Some label nobody translated');
});

it('registers the upgrade command', function () {
    expect(array_keys(app(Illuminate\Contracts\Console\Kernel::class)->all()))
        ->toContain('nova-field-indicator:upgrade');
});

it('points the command at the right class', function () {
    expect(app(Illuminate\Contracts\Console\Kernel::class)->all()['nova-field-indicator:upgrade'])
        ->toBeInstanceOf(UpgradeCommand::class);
});
