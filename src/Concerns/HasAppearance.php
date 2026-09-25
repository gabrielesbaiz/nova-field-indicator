<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Closure;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Shape;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Size;
use Gabrielesbaiz\NovaFieldIndicator\Support\State;

/**
 * Presentation: shape, size, layout, motion and tooltips.
 *
 * All of it is serialized as tokens rather than CSS, so the stylesheet keeps
 * ownership of every measurement and a new size is a CSS change, not a payload
 * change.
 */
trait HasAppearance
{
    protected ?Shape $shape = null;

    protected ?Size $size = null;

    /** @var Closure(mixed, mixed): bool|bool|array<array-key, mixed>|null */
    protected Closure|bool|array|null $pulse = null;

    protected ?Closure $tooltipCallback = null;

    /**
     * @return $this
     */
    public function shape(Shape|string $shape): static
    {
        $this->shape = $shape instanceof Shape ? $shape : (Shape::tryFrom($shape) ?? Shape::Dot);

        return $this;
    }

    /**
     * @return $this
     */
    public function size(Size|string $size): static
    {
        $this->size = $size instanceof Size ? $size : (Size::tryFrom($size) ?? Size::Medium);

        return $this;
    }

    /** @return $this */
    public function dot(): static
    {
        return $this->shape(Shape::Dot);
    }

    /** @return $this */
    public function ring(): static
    {
        return $this->shape(Shape::Ring);
    }

    /** @return $this */
    public function pill(): static
    {
        return $this->shape(Shape::Pill);
    }

    /** @return $this */
    public function square(): static
    {
        return $this->shape(Shape::Square);
    }

    /**
     * Lay the mark and its label out on one line.
     *
     * Writes Nova's own $inline property rather than introducing a second one:
     * Field already declares it and already serializes it.
     *
     * @return $this
     */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * Animate the mark, for states that mean "in progress".
     *
     * The keyframes are wrapped in a prefers-reduced-motion query in the
     * stylesheet, so this never overrides a user's motion preference.
     *
     * @param  Closure(mixed, mixed): bool|bool|array<array-key, mixed>  $when
     * @return $this
     */
    public function pulse(Closure|bool|array $when = true): static
    {
        $this->pulse = $when;

        return $this;
    }

    /**
     * @param  Closure(mixed, mixed): (string|null)  $callback
     * @return $this
     */
    public function tooltipUsing(Closure $callback): static
    {
        $this->tooltipCallback = $callback;

        return $this;
    }

    protected function resolvedShape(): Shape
    {
        if ($this->shape instanceof Shape) {
            return $this->shape;
        }

        $configured = config('nova-field-indicator.appearance.shape', 'dot');

        return is_string($configured) ? (Shape::tryFrom($configured) ?? Shape::Dot) : Shape::Dot;
    }

    protected function resolvedSize(): Size
    {
        if ($this->size instanceof Size) {
            return $this->size;
        }

        $configured = config('nova-field-indicator.appearance.size', 'md');

        return is_string($configured) ? (Size::tryFrom($configured) ?? Size::Medium) : Size::Medium;
    }

    protected function resolvePulseFor(string|int|null $key, ?State $state, mixed $resource): bool
    {
        if ($state?->pulse !== null) {
            return $state->pulse;
        }

        if ($this->pulse instanceof Closure) {
            return (bool) ($this->pulse)($this->value, $resource);
        }

        if (is_array($this->pulse)) {
            return $key !== null && in_array($key, array_map(
                static fn (mixed $v): string|int|null => \Gabrielesbaiz\NovaFieldIndicator\Support\ValueNormalizer::key($v),
                $this->pulse,
            ), true);
        }

        return (bool) $this->pulse;
    }

    protected function resolveTooltipFor(?State $state, mixed $resource): ?string
    {
        if ($this->tooltipCallback !== null) {
            $tooltip = ($this->tooltipCallback)($this->value, $resource);

            return $tooltip === null ? null : (string) $tooltip;
        }

        return $state?->tooltip;
    }
}
