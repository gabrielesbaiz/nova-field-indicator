<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;
use Traversable;
use UnitEnum;

/**
 * Reduces an attribute value to a lookup key.
 *
 * This is what lets the package use strict comparison everywhere without the
 * behaviour regression that would normally imply. 2.x compared with `==` and
 * `in_array(..., false)`, so `0` matched `'0'`; switching to `===` alone would
 * silently stop hiding rows that used to disappear. Normalising both sides
 * first keeps the comparison forgiving for scalars while staying strict.
 */
final class ValueNormalizer
{
    /**
     * Normalise one value into something usable as an array key.
     *
     * Integers stay integers so that both ['1' => ...] and [1 => ...] resolve,
     * since PHP casts numeric string keys to int on array insert anyway.
     */
    public static function key(mixed $value): string|int|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            // A float key cannot round-trip through an array, so it becomes a
            // string; ranges() handles numeric comparison separately.
            return (string) $value;
        }

        if ($value instanceof Stringable || (is_object($value) && method_exists($value, '__toString'))) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        // A numeric string becomes an int so it collides with the int form.
        if ($value !== '' && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Split a value into the list of keys it should render indicators for.
     *
     * A scalar yields one key; an array or Collection yields many, which is how
     * a JSON-cast tag column renders several marks with no extra branching in
     * the Vue component.
     *
     * @return list<string|int|null>
     */
    public static function list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        } elseif ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            return array_values(array_map(self::key(...), $value));
        }

        return [self::key($value)];
    }

    /**
     * Whether a value counts as "empty" for hideWhenEmpty().
     *
     * Mirrors 2.x's `! $value` so an upgrade does not change which rows vanish.
     */
    public static function isEmpty(mixed $value): bool
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_string($value)) {
            return $value === '' || $value === '0';
        }

        return blank($value) || $value === 0 || $value === 0.0 || $value === false;
    }
}
