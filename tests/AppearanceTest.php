<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\Enums\IconType;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Shape;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Size;
use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;

function appearance(mixed $value, ?callable $configure = null): array
{
    $field = NovaFieldIndicator::make('Status', 'status');
    $configure?->__invoke($field);
    $field->value = $value;

    return $field->jsonSerialize();
}

it('accepts a shape as an enum or a string', function () {
    expect(appearance('a', fn ($f) => $f->shape(Shape::Ring))['shape'])->toBe('ring')
        ->and(appearance('a', fn ($f) => $f->shape('square'))['shape'])->toBe('square');
});

it('falls back to a dot for an unknown shape string', function () {
    expect(appearance('a', fn ($f) => $f->shape('triangle'))['shape'])->toBe('dot');
});

it('exposes a shorthand for every shape', function () {
    expect(appearance('a', fn ($f) => $f->dot())['shape'])->toBe('dot')
        ->and(appearance('a', fn ($f) => $f->ring())['shape'])->toBe('ring')
        ->and(appearance('a', fn ($f) => $f->pill())['shape'])->toBe('pill')
        ->and(appearance('a', fn ($f) => $f->square())['shape'])->toBe('square');
});

it('accepts a size as an enum or a string', function () {
    expect(appearance('a', fn ($f) => $f->size(Size::Large))['size'])->toBe('lg')
        ->and(appearance('a', fn ($f) => $f->size('sm'))['size'])->toBe('sm');
});

it('reads shape and size defaults from config', function () {
    config()->set('nova-field-indicator.appearance.shape', 'pill');
    config()->set('nova-field-indicator.appearance.size', 'lg');

    expect(appearance('a')['shape'])->toBe('pill')
        ->and(appearance('a')['size'])->toBe('lg');
});

it('toggles pulse with a boolean', function () {
    expect(appearance('a', fn ($f) => $f->pulse())['indicators'][0]['pulse'])->toBeTrue()
        ->and(appearance('a')['indicators'][0]['pulse'])->toBeFalse();
});

it('toggles pulse with a closure', function () {
    $configure = fn ($f) => $f->pulse(static fn (mixed $value): bool => $value === 'syncing');

    expect(appearance('syncing', $configure)['indicators'][0]['pulse'])->toBeTrue()
        ->and(appearance('idle', $configure)['indicators'][0]['pulse'])->toBeFalse();
});

it('toggles pulse for a list of values', function () {
    $configure = fn ($f) => $f->pulse(['syncing', 'pending']);

    expect(appearance('pending', $configure)['indicators'][0]['pulse'])->toBeTrue()
        ->and(appearance('idle', $configure)['indicators'][0]['pulse'])->toBeFalse();
});

it('lets a state override the field-level pulse', function () {
    $json = appearance('idle', fn ($f) => $f->pulse()->states(['idle' => ['pulse' => false]]));

    expect($json['indicators'][0]['pulse'])->toBeFalse();
});

it('resolves a tooltip from the state table or a closure', function () {
    expect(appearance('a', fn ($f) => $f->descriptions(['a' => 'From table']))['indicators'][0]['tooltip'])
        ->toBe('From table')
        ->and(appearance('a', fn ($f) => $f->tooltipUsing(static fn (): string => 'From closure'))['indicators'][0]['tooltip'])
        ->toBe('From closure');
});

it('leaves the tooltip null when none is configured', function () {
    expect(appearance('a')['indicators'][0]['tooltip'])->toBeNull();
});

it('serializes the configured icon type', function () {
    $json = appearance('a', fn ($f) => $f->icons(['a' => 'bolt'])->iconType(IconType::Micro));

    expect($json['indicators'][0]['icon'])->toBe(['name' => 'bolt', 'type' => 'micro']);
});

it('turns icons on implicitly when an icon map is given', function () {
    expect(appearance('a', fn ($f) => $f->icons(['a' => 'bolt']))['indicators'][0]['icon']['name'])
        ->toBe('bolt');
});

it('supplies a semantic icon for a token when withIcons is bare', function () {
    // ->withIcons() with no map should already be useful.
    $json = appearance('a', fn ($f) => $f->colors(['a' => 'danger'])->withIcons());

    expect($json['indicators'][0]['icon']['name'])->toBe('x-circle');
});

it('never leaves the accessible name empty under withoutLabel', function () {
    $json = appearance('a', fn ($f) => $f->withoutLabel());

    expect($json['indicators'][0]['label'])->toBeNull()
        ->and($json['indicators'][0]['ariaLabel'])->not->toBeEmpty();
});
