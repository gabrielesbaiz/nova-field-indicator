<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;

/**
 * Numeric lower bounds, for the ->ranges() API.
 *
 * Covers the case the state table cannot express: scores, health percentages,
 * stock levels — values that are continuous rather than enumerable. Keys are
 * inclusive lower bounds, and the set is sorted once at construction so the
 * per-row lookup is a linear scan over an already-ordered list.
 */
final class ThresholdSet
{
    /** @param list<array{0: float, 1: State}> $thresholds Sorted descending. */
    private function __construct(private array $thresholds) {}

    /**
     * @param  array<array-key, mixed>  $ranges
     */
    public static function make(array $ranges): self
    {
        $thresholds = [];

        foreach ($ranges as $bound => $definition) {
            if (! is_numeric($bound)) {
                throw IndicatorException::invalidRangeKey($bound);
            }

            // A bare string in a range is a colour, not a label: ranges exist to
            // colour a number, and the number itself is the natural label.
            $state = is_array($definition) || $definition instanceof State
                ? State::make($definition)
                : new State(color: is_string($definition) ? $definition : null);

            $thresholds[] = [(float) $bound, $state];
        }

        usort($thresholds, static fn (array $a, array $b): int => $b[0] <=> $a[0]);

        return new self($thresholds);
    }

    public function isEmpty(): bool
    {
        return $this->thresholds === [];
    }

    /**
     * The state for a value, or null when it is non-numeric or below every bound.
     */
    public function match(mixed $value): ?State
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        foreach ($this->thresholds as [$bound, $state]) {
            if ($number >= $bound) {
                return $state;
            }
        }

        return null;
    }

    /**
     * @return list<float>
     */
    public function bounds(): array
    {
        return array_map(static fn (array $t): float => $t[0], $this->thresholds);
    }
}
