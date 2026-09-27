<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;

function labelOf(mixed $value, ?callable $configure = null): ?string
{
    $field = NovaFieldIndicator::make('Status', 'status');
    $configure?->__invoke($field);
    $field->value = $value;

    return $field->jsonSerialize()['indicators'][0]['label'];
}

it('looks a label up in the state table', function () {
    expect(labelOf('active', fn ($f) => $f->labels(['active' => 'Active'])))->toBe('Active');
});

/*
 * 1.x needed ->withoutLabels() to print the raw value. In 2.0 that is simply
 * the default — an unmapped value falls back to itself, as Nova's Badge does —
 * which is why the old method no longer exists.
 */
it('falls back to the raw value when a key is unmapped', function () {
    expect(labelOf('active'))->toBe('active')
        ->and(labelOf('pending', fn ($f) => $f->labels(['active' => 'Active'])))->toBe('pending');
});

it('prefers the unknown label over the raw value', function () {
    expect(labelOf('pending', fn ($f) => $f->labels(['active' => 'Active'])->unknown('Unknown')))
        ->toBe('Unknown');
});

it('lets unknown() also set the fallback colour', function () {
    $field = NovaFieldIndicator::make('Status', 'status')->unknown('Unknown', 'danger');
    $field->value = 'nope';

    expect($field->jsonSerialize()['indicators'][0]['color']['light'])
        ->toBe('rgba(var(--colors-red-500))');
});

it('lets a closure override every lookup', function () {
    expect(labelOf('active', fn ($f) => $f
        ->labels(['active' => 'Active'])
        ->labelUsing(static fn (mixed $value): string => strtoupper((string) $value))))
        ->toBe('ACTIVE');
});

it('passes the resource to the label closure', function () {
    $field = NovaFieldIndicator::make('Status', 'status')
        ->labelUsing(static fn (mixed $value, mixed $resource): string => $resource->name);
    $field->value = 'active';
    $field->resource = (object) ['name' => 'From resource'];

    expect($field->jsonSerialize()['indicators'][0]['label'])->toBe('From resource');
});

it('accepts a Stringable label', function () {
    expect(labelOf('active', fn ($f) => $f->labels(['active' => str('Active')])))->toBe('Active');
});

it('matches integer and numeric string keys interchangeably', function () {
    expect(labelOf(1, fn ($f) => $f->labels(['1' => 'One'])))->toBe('One')
        ->and(labelOf('1', fn ($f) => $f->labels([1 => 'One'])))->toBe('One');
});

it('translates labels by default', function () {
    app('translator')->addLines(['*.Active' => 'Attivo'], 'it');
    app()->setLocale('it');

    expect(labelOf('active', fn ($f) => $f->labels(['active' => 'Active'])))->toBe('Attivo');
});

it('can opt out of translation for user-generated labels', function () {
    app('translator')->addLines(['*.Active' => 'Attivo'], 'it');
    app()->setLocale('it');

    expect(labelOf('active', fn ($f) => $f->labels(['active' => 'Active'])->withoutTranslation()))
        ->toBe('Active');
});

it('does not translate a closure result, which owns its own wording', function () {
    app('translator')->addLines(['*.Active' => 'Attivo'], 'it');
    app()->setLocale('it');

    expect(labelOf('active', fn ($f) => $f->labelUsing(static fn (): string => 'Active')))
        ->toBe('Active');
});

it('honours the translate toggle in config', function () {
    config()->set('nova-field-indicator.labels.translate', false);
    app('translator')->addLines(['*.Active' => 'Attivo'], 'it');
    app()->setLocale('it');

    expect(labelOf('active', fn ($f) => $f->labels(['active' => 'Active'])))->toBe('Active');
});
