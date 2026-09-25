<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use BackedEnum;
use Closure;
use Gabrielesbaiz\NovaFieldIndicator\Support\ValueNormalizer;

/**
 * Conditional visibility: render nothing for certain values.
 *
 * Replaces 2.x's shouldHide()/shouldHideIfNo(). The rename is not cosmetic —
 * the old implementation tested is_callable() before treating the argument as a
 * value, so ->shouldHide('count') silently *invoked* count() as the predicate
 * instead of comparing against the string "count". Dispatching on the declared
 * type instead makes that class of bug unrepresentable: a Closure is a
 * callback, anything else is data. A first-class callable is a Closure, so
 * ->hideWhen($this->check(...)) still works.
 */
trait HidesValues
{
    /** @var Closure(mixed, mixed): bool|array<array-key, mixed>|string|int|float|bool|BackedEnum|null */
    protected Closure|array|string|int|float|bool|BackedEnum|null $hideWhen = null;

    protected bool $hideWhenEmpty = false;

    /**
     * @param  Closure(mixed, mixed): bool|array<array-key, mixed>|string|int|float|bool|BackedEnum  $value
     * @return $this
     */
    public function hideWhen(Closure|array|string|int|float|bool|BackedEnum $value): static
    {
        $this->hideWhen = $value;

        return $this;
    }

    /**
     * Hide falsy values — null, false, 0, '' and '0'.
     *
     * @return $this
     */
    public function hideWhenEmpty(bool $hide = true): static
    {
        $this->hideWhenEmpty = $hide;

        return $this;
    }

    protected function shouldHideValue(mixed $value, mixed $resource = null): bool
    {
        if ($this->hideWhenEmpty && ValueNormalizer::isEmpty($value)) {
            return true;
        }

        if ($this->hideWhen === null) {
            return false;
        }

        if ($this->hideWhen instanceof Closure) {
            return (bool) ($this->hideWhen)($value, $resource);
        }

        $key = ValueNormalizer::key($value);

        if (is_array($this->hideWhen)) {
            $candidates = array_map(ValueNormalizer::key(...), $this->hideWhen);

            // Strict, but only because both sides were normalised first: 0 and
            // '0' collapse to the same key, so this stays as forgiving as 2.x's
            // loose comparison without its surprises.
            return in_array($key, $candidates, true);
        }

        return $key === ValueNormalizer::key($this->hideWhen);
    }
}
