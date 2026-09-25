<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\Status;

function payload(mixed $value, ?callable $configure = null): array
{
    $field = NovaFieldIndicator::make('Status', 'status');
    $configure?->__invoke($field);
    $field->value = $value;

    return $field->jsonSerialize();
}

/*
 * The efficiency fix. 2.x pushed the whole labels and colors configuration into
 * every row's payload and looked it up again in the browser; 3.0 sends one
 * resolved object and nothing else.
 */
it('never ships the configuration maps to the client', function () {
    $json = payload('active', fn ($f) => $f
        ->labels(['active' => 'Active', 'banned' => 'Banned', 'invited' => 'Invited'])
        ->colors(['active' => 'success', 'banned' => 'danger', 'invited' => 'info']));

    expect($json)->not->toHaveKeys(['labels', 'colors', 'unknownLabel', 'withoutLabels'])
        ->and($json)->toHaveKey('indicators');
});

it('always serializes indicators as a list', function () {
    expect(payload('active')['indicators'])->toBeArray()->toHaveCount(1)
        ->and(array_is_list(payload('active')['indicators']))->toBeTrue();
});

it('renders one indicator per entry for an array value', function () {
    $json = payload(['active', 'banned'], fn ($f) => $f->labels([
        'active' => 'Active',
        'banned' => 'Banned',
    ]));

    expect($json['indicators'])->toHaveCount(2)
        ->and($json['indicators'][0]['label'])->toBe('Active')
        ->and($json['indicators'][1]['label'])->toBe('Banned');
});

it('renders one indicator per entry for a Collection value', function () {
    $json = payload(collect(['active', 'banned']));

    expect($json['indicators'])->toHaveCount(2);
});

it('carries the full resolved shape for one indicator', function () {
    $json = payload('active', fn ($f) => $f
        ->labels(['active' => 'Active'])
        ->colors(['active' => 'success'])
        ->descriptions(['active' => 'Account is active'])
        ->withIcons());

    $indicator = $json['indicators'][0];

    expect($indicator)->toHaveKeys(['value', 'label', 'ariaLabel', 'tooltip', 'color', 'icon', 'pulse'])
        ->and($indicator['value'])->toBe('active')
        ->and($indicator['label'])->toBe('Active')
        ->and($indicator['tooltip'])->toBe('Account is active')
        ->and($indicator['icon'])->toBe(['name' => 'check-circle', 'type' => 'solid'])
        ->and($indicator['color'])->toHaveKeys(['token', 'light', 'dark', 'soft'])
        ->and($indicator['color']['light'])->toBe('rgba(var(--colors-green-500))')
        ->and($indicator['color']['dark'])->toBe('rgba(var(--colors-green-400))');
});

it('serializes the presentation tokens, not measurements', function () {
    $json = payload('active', fn ($f) => $f->pill()->size('lg'));

    expect($json['shape'])->toBe('pill')
        ->and($json['size'])->toBe('lg');
});

it('nulls the label but never the accessible name under withoutLabel', function () {
    // With no visible text the colour alone carries meaning, so the aria-label
    // becomes the only accessible name the mark has.
    $json = payload('active', fn ($f) => $f->labels(['active' => 'Active'])->withoutLabel());

    $indicator = $json['indicators'][0];

    expect($indicator['label'])->toBeNull()
        ->and($indicator['ariaLabel'])->toBe('Status: Active');
});

it('uses the plain label as the accessible name when text is visible', function () {
    $json = payload('active', fn ($f) => $f->labels(['active' => 'Active']));

    expect($json['indicators'][0]['ariaLabel'])->toBe('Active');
});

it('resolves a single indicator for a null value so unknown() still shows', function () {
    $json = payload(null, fn ($f) => $f->unknown('Unknown'));

    expect($json['indicators'])->toHaveCount(1)
        ->and($json['indicators'][0]['label'])->toBe('Unknown');
});

it('omits the icon entirely when icons are off', function () {
    expect(payload('active', fn ($f) => $f->colors(['active' => 'success']))['indicators'][0]['icon'])
        ->toBeNull();
});

it('unwraps a backed enum value', function () {
    $json = payload(Status::Active);

    expect($json['indicators'][0]['value'])->toBe('active');
});

it('exposes the empty text for the client to render', function () {
    expect(payload(null)['emptyText'])->toBe('—');
});
