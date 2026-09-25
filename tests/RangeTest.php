<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;
use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;

function rangeColor(mixed $value, array $ranges): string
{
    $field = NovaFieldIndicator::make('Score', 'score')->ranges($ranges);
    $field->value = $value;

    return $field->jsonSerialize()['indicators'][0]['color']['light'];
}

it('treats range keys as inclusive lower bounds', function () {
    $ranges = [0 => 'red', 50 => 'yellow', 80 => 'green'];

    expect(rangeColor(80, $ranges))->toBe('rgba(var(--colors-green-500))')
        ->and(rangeColor(79, $ranges))->toBe('rgba(var(--colors-yellow-500))')
        ->and(rangeColor(50, $ranges))->toBe('rgba(var(--colors-yellow-500))')
        ->and(rangeColor(49, $ranges))->toBe('rgba(var(--colors-red-500))')
        ->and(rangeColor(0, $ranges))->toBe('rgba(var(--colors-red-500))');
});

it('sorts bounds itself, so declaration order does not matter', function () {
    $shuffled = [80 => 'green', 0 => 'red', 50 => 'yellow'];

    expect(rangeColor(85, $shuffled))->toBe('rgba(var(--colors-green-500))')
        ->and(rangeColor(10, $shuffled))->toBe('rgba(var(--colors-red-500))');
});

it('falls back to the default colour below the lowest bound', function () {
    expect(rangeColor(-5, [0 => 'red', 50 => 'green']))->toBe('rgba(var(--colors-gray-400))');
});

it('ignores ranges for a non-numeric value', function () {
    expect(rangeColor('nonsense', [0 => 'red']))->toBe('rgba(var(--colors-gray-400))');
});

it('accepts a nested state array for a band', function () {
    $field = NovaFieldIndicator::make('Score', 'score')->ranges([
        0 => ['label' => 'Critical', 'color' => 'danger', 'icon' => 'x-circle'],
        50 => ['label' => 'Healthy', 'color' => 'success'],
    ])->withIcons();
    $field->value = 10;

    $indicator = $field->jsonSerialize()['indicators'][0];

    expect($indicator['label'])->toBe('Critical')
        ->and($indicator['color']['light'])->toBe('rgba(var(--colors-red-500))')
        ->and($indicator['icon']['name'])->toBe('x-circle');
});

it('handles float bounds and values', function () {
    expect(rangeColor(0.5, [0 => 'red', 0.4 => 'green']))->toBe('rgba(var(--colors-green-500))');
});

it('rejects a non-numeric range key', function () {
    expect(fn () => NovaFieldIndicator::make('Score')->ranges(['low' => 'red']))
        ->toThrow(IndicatorException::class);
});
