<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\Status;

function indicatorFor(mixed $value, ?callable $configure = null): array
{
    $field = NovaFieldIndicator::make('Status', 'status');

    if ($configure !== null) {
        $configure($field);
    }

    $field->value = $value;

    return $field->jsonSerialize();
}

/*
 * The headline regression. 1.x tested is_callable() before treating the
 * argument as a value, so ->shouldHide('trim') *invoked* trim() as the
 * predicate instead of comparing the string. hideWhen() dispatches on the
 * declared type, which makes that unrepresentable.
 */
it('treats a string naming a PHP function as a value, not a callback', function () {
    $hidden = indicatorFor('active', fn ($f) => $f->hideWhen('trim'));
    expect($hidden['shouldHide'])->toBeFalse();

    $shown = indicatorFor('trim', fn ($f) => $f->hideWhen('trim'));
    expect($shown['shouldHide'])->toBeTrue();
});

it('still accepts a first-class callable, which is a Closure', function () {
    $payload = indicatorFor('active', fn ($f) => $f->hideWhen(
        static fn (mixed $value): bool => $value === 'active',
    ));

    expect($payload['shouldHide'])->toBeTrue();
});

it('hides on a scalar match', function () {
    expect(indicatorFor('active', fn ($f) => $f->hideWhen('active'))['shouldHide'])->toBeTrue()
        ->and(indicatorFor('banned', fn ($f) => $f->hideWhen('active'))['shouldHide'])->toBeFalse();
});

it('hides on any member of an array', function () {
    $configure = fn ($f) => $f->hideWhen(['invited', 'requested']);

    expect(indicatorFor('invited', $configure)['shouldHide'])->toBeTrue()
        ->and(indicatorFor('requested', $configure)['shouldHide'])->toBeTrue()
        ->and(indicatorFor('active', $configure)['shouldHide'])->toBeFalse();
});

/*
 * Comparison is strict, but both sides are normalised first — so 0 and '0'
 * still collapse to the same key. Without that, adopting strict_comparison
 * would have silently stopped hiding rows that 1.x hid via ==.
 */
it('keeps loose-feeling scalar matching despite strict comparison', function () {
    expect(indicatorFor('0', fn ($f) => $f->hideWhen(0))['shouldHide'])->toBeTrue()
        ->and(indicatorFor(0, fn ($f) => $f->hideWhen('0'))['shouldHide'])->toBeTrue()
        ->and(indicatorFor(5, fn ($f) => $f->hideWhen('5'))['shouldHide'])->toBeTrue();
});

it('does not match an unrelated scalar', function () {
    expect(indicatorFor(0, fn ($f) => $f->hideWhen('active'))['shouldHide'])->toBeFalse();
});

it('unwraps a backed enum on both sides', function () {
    expect(indicatorFor(Status::Banned, fn ($f) => $f->hideWhen(Status::Banned))['shouldHide'])->toBeTrue()
        ->and(indicatorFor(Status::Active, fn ($f) => $f->hideWhen(Status::Banned))['shouldHide'])->toBeFalse()
        ->and(indicatorFor('banned', fn ($f) => $f->hideWhen(Status::Banned))['shouldHide'])->toBeTrue();
});

it('hides every falsy value under hideWhenEmpty', function (mixed $value) {
    expect(indicatorFor($value, fn ($f) => $f->hideWhenEmpty())['shouldHide'])->toBeTrue();
})->with([[null], [false], [0], [0.0], [''], ['0'], [[]]]);

it('keeps truthy values under hideWhenEmpty', function (mixed $value) {
    expect(indicatorFor($value, fn ($f) => $f->hideWhenEmpty())['shouldHide'])->toBeFalse();
})->with([['active'], [1], [true], ['0.0'], [['a']]]);

it('resolves nothing at all when hidden', function () {
    // The short-circuit is the point: a hidden field should not pay for
    // resolution on every row of an index.
    $payload = indicatorFor('active', fn ($f) => $f->hideWhen('active')->labels(['active' => 'Active']));

    expect($payload['shouldHide'])->toBeTrue()
        ->and($payload['indicators'])->toBe([]);
});
