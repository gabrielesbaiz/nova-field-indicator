<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;
use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Gabrielesbaiz\NovaFieldIndicator\Support\EnumStates;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\PlainStatus;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\Status;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\UnbackedStatus;

function enumPayload(mixed $value, ?callable $configure = null): array
{
    $field = NovaFieldIndicator::make('Status', 'status');
    $configure?->__invoke($field);
    $field->value = $value;

    return $field->jsonSerialize();
}

it('derives labels, colours and icons from one enum', function () {
    $json = enumPayload('active', fn ($f) => $f->enum(Status::class)->withIcons());

    $indicator = $json['indicators'][0];

    expect($indicator['label'])->toBe('Active')
        ->and($indicator['color']['light'])->toBe('rgba(var(--colors-green-500))')
        ->and($indicator['icon']['name'])->toBe('check-circle');
});

/*
 * The best half of the feature: an enum-cast attribute describes itself, so a
 * field over it needs no configuration at all.
 */
it('self-describes when the value is a backed enum, with no configuration', function () {
    $json = enumPayload(Status::Banned, fn ($f) => $f->withIcons());

    $indicator = $json['indicators'][0];

    expect($indicator['label'])->toBe('Banned')
        ->and($indicator['color']['light'])->toBe('rgba(var(--colors-red-500))')
        ->and($indicator['icon']['name'])->toBe('x-circle');
});

it('humanises the case name when the enum implements no contracts', function () {
    $json = enumPayload('in_review', fn ($f) => $f->enum(PlainStatus::class));

    expect($json['indicators'][0]['label'])->toBe('In review');
});

it('humanises a studly case name', function () {
    $json = enumPayload('publishedLate', fn ($f) => $f->enum(PlainStatus::class));

    expect($json['indicators'][0]['label'])->toBe('Published late');
});

it('lets explicit configuration win over the derived table', function () {
    $json = enumPayload('active', fn ($f) => $f
        ->labels(['active' => 'Still here'])
        ->enum(Status::class));

    expect($json['indicators'][0]['label'])->toBe('Still here');
});

it('rejects an enum that is not backed', function () {
    expect(fn () => NovaFieldIndicator::make('Status')->enum(UnbackedStatus::class))
        ->toThrow(IndicatorException::class);
});

it('rejects a class that is not an enum', function () {
    expect(fn () => NovaFieldIndicator::make('Status')->enum(stdClass::class))
        ->toThrow(IndicatorException::class);
});

/*
 * Without memoization this reflection would run once per row, which on a
 * 50-row index is 50 passes over cases() to produce the same table.
 */
it('memoizes the derived table per enum class', function () {
    EnumStates::flush();

    $first = EnumStates::for(Status::class);
    $second = EnumStates::for(Status::class);

    expect($second)->toBe($first);
});

it('exposes enum values in declaration order for the filter', function () {
    expect(EnumStates::values(Status::class))->toBe(['active', 'banned', 'invited']);
});

it('renders several indicators for an array of enums', function () {
    $json = enumPayload([Status::Active, Status::Banned]);

    expect($json['indicators'])->toHaveCount(2)
        ->and($json['indicators'][0]['label'])->toBe('Active')
        ->and($json['indicators'][1]['label'])->toBe('Banned');
});

it('builds a ready-made boolean state table', function () {
    $true = enumPayload(true, fn ($f) => $f->boolean()->withIcons());
    $false = enumPayload(false, fn ($f) => $f->boolean());

    expect($true['indicators'][0]['label'])->toBe('Yes')
        ->and($true['indicators'][0]['color']['light'])->toBe('rgba(var(--colors-green-500))')
        ->and($true['indicators'][0]['icon']['name'])->toBe('check-circle')
        ->and($false['indicators'][0]['label'])->toBe('No');
});

it('accepts custom boolean labels', function () {
    $json = enumPayload(true, fn ($f) => $f->boolean('Enabled', 'Disabled'));

    expect($json['indicators'][0]['label'])->toBe('Enabled');
});
