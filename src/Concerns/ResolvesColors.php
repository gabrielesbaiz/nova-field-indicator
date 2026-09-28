<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Closure;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;
use Gabrielesbaiz\NovaFieldIndicator\Results\ResolvedColor;
use Gabrielesbaiz\NovaFieldIndicator\Support\ColorResolver;
use Gabrielesbaiz\NovaFieldIndicator\Support\State;
use Gabrielesbaiz\NovaFieldIndicator\Support\ThresholdSet;

/**
 * Colour resolution, plus the numeric ->ranges() escape hatch.
 */
trait ResolvesColors
{
    protected ?Closure $colorCallback = null;

    /** @var Color|string|array<string, mixed>|null */
    protected Color|string|array|null $defaultColor = null;

    protected ?ThresholdSet $ranges = null;

    protected ?ColorResolver $colorResolver = null;

    /**
     * @param  Closure(mixed, mixed): mixed  $callback
     * @return $this
     */
    public function colorUsing(Closure $callback): static
    {
        $this->colorCallback = $callback;

        return $this;
    }

    /**
     * Colour used for a value with no state of its own.
     *
     * @param  Color|string|array<string, mixed>  $color
     * @return $this
     */
    public function defaultColor(Color|string|array $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    /**
     * Colour a continuous value by inclusive lower bounds.
     *
     * For scores, percentages and stock levels — the values a state table
     * cannot enumerate. Keys are the lower bound of each band.
     *
     * @param  array<array-key, mixed>  $ranges
     * @return $this
     */
    public function ranges(array $ranges): static
    {
        $this->ranges = ThresholdSet::make($ranges);

        return $this;
    }

    protected function resolveColorFor(string|int|null $key, ?State $state, mixed $resource): ResolvedColor
    {
        $resolver = $this->colorResolver();

        if ($this->colorCallback !== null) {
            /** @var Color|string|array<string, mixed>|null $color */
            $color = ($this->colorCallback)($this->value, $resource);

            return $resolver->resolve($color, $this->fallbackColor());
        }

        if ($state?->color !== null) {
            return $resolver->resolve($state->color, $this->fallbackColor());
        }

        // A hand-written state can carry a label but no colour, and still want
        // the range band's colour underneath it — so this stays. The scan is
        // memoized, so asking twice for the same value costs one match.
        if (($rangeState = $this->rangeStateFor())?->color !== null) {
            return $resolver->resolve($rangeState->color, $this->fallbackColor());
        }

        return $resolver->resolve($this->defaultColor, $this->fallbackColor());
    }

    protected function fallbackColor(): Color
    {
        $configured = $this->setting('colors', 'default', 'gray');

        return is_string($configured)
            ? (Color::tryFrom($configured) ?? Color::Gray)
            : Color::Gray;
    }

    protected function colorResolver(): ColorResolver
    {
        if ($this->colorResolver instanceof ColorResolver) {
            return $this->colorResolver;
        }

        /** @var array<string, mixed> $tokens */
        $tokens = $this->setting('colors', 'tokens', []);

        /** @var array{light?: int, dark?: int} $shades */
        $shades = $this->setting('colors', 'shades', ['light' => 500, 'dark' => 400]);

        return $this->colorResolver = new ColorResolver(
            tokens: is_array($tokens) ? $tokens : [],
            shades: [
                'light' => (int) ($shades['light'] ?? 500),
                'dark' => (int) ($shades['dark'] ?? 400),
            ],
            softAlpha: (int) $this->setting('colors', 'soft_alpha', 15),
        );
    }
}
