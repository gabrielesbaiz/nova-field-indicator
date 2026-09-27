<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\Status;
use Laravel\Nova\Fields\Filters\SelectFilter;
use Laravel\Nova\Fields\Unfillable;
use Laravel\Nova\Http\Requests\NovaRequest;

function novaRequest(): NovaRequest
{
    return NovaRequest::create('/');
}

it('is unfillable, so nothing can write through it', function () {
    expect(NovaFieldIndicator::make('Status'))->toBeInstanceOf(Unfillable::class);
});

/*
 * Replaces 1.x's $showOnCreation/$showOnUpdate pair, which missed the attach
 * and update-attached views.
 */
it('is hidden on every form view by default', function () {
    $field = NovaFieldIndicator::make('Status');

    expect($field->showOnCreation)->toBeFalse()
        ->and($field->showOnUpdate)->toBeFalse()
        ->and($field->showOnIndex)->toBeTrue()
        ->and($field->showOnDetail)->toBeTrue();
});

it('offers no filter until filterable() is called', function () {
    $field = NovaFieldIndicator::make('Status')->labels(['active' => 'Active']);

    expect($field->resolveFilter(novaRequest()))->toBeNull();
});

it('offers a select filter once filterable', function () {
    $field = NovaFieldIndicator::make('Status')->labels(['active' => 'Active'])->filterable();

    expect($field->resolveFilter(novaRequest()))->toBeInstanceOf(SelectFilter::class);
});

/*
 * Nova's own Badge builds filter options solely from map(), so its filter is
 * empty unless you happened to call it. Deriving options from the state table
 * and the enum avoids that.
 */
it('builds filter options from the state table', function () {
    $field = NovaFieldIndicator::make('Status')
        ->labels(['active' => 'Active', 'banned' => 'Banned'])
        ->filterable();

    $options = $field->serializeForFilter()['options'];

    expect($options)->toBe([
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'banned', 'label' => 'Banned'],
    ]);
});

it('builds filter options from an enum', function () {
    $field = NovaFieldIndicator::make('Status')->enum(Status::class)->filterable();

    expect($field->serializeForFilter()['options'])->toBe([
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'banned', 'label' => 'Banned'],
        ['value' => 'invited', 'label' => 'Invited'],
    ]);
});

it('hides the filter when no options are known', function () {
    // A field configured only with a colour closure has nothing to offer, so it
    // should not render an empty dropdown.
    $field = NovaFieldIndicator::make('Status')
        ->colorUsing(static fn (): string => 'success')
        ->filterable();

    expect($field->resolveFilter(novaRequest()))->toBeNull();
});

/*
 * serializeForFilter() must call parent::jsonSerialize(), exactly as Badge
 * does. Calling $this->jsonSerialize() would resolve an indicator for whatever
 * value happened to be loaded and ship it inside every filter payload.
 */
it('keeps resolved indicators out of the filter payload', function () {
    $field = NovaFieldIndicator::make('Status')->labels(['active' => 'Active'])->filterable();
    $field->value = 'active';

    $serialized = $field->serializeForFilter();

    expect($serialized)->not->toHaveKey('indicators')
        ->and($serialized)->toHaveKeys(['uniqueKey', 'name', 'attribute', 'options']);
});

it('translates filter option labels', function () {
    app('translator')->addLines(['*.Active' => 'Attivo'], 'it');
    app()->setLocale('it');

    $field = NovaFieldIndicator::make('Status')->labels(['active' => 'Active'])->filterable();

    expect($field->serializeForFilter()['options'][0]['label'])->toBe('Attivo');
});
