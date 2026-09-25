<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;
use Stringable;

/**
 * One row of the state table: how a single attribute value should look.
 *
 * State::make() is the normaliser that lets the focused setters (labels(),
 * colors(), icons()) and the one-call states() write into the same structure,
 * so the rest of the package only ever reads one shape.
 */
final class State
{
    /**
     * @param  Color|string|array<string, mixed>|null  $color
     */
    public function __construct(
        public string|int|null $key = null,
        public Stringable|string|null $label = null,
        public Color|string|array|null $color = null,
        public ?string $icon = null,
        public ?string $tooltip = null,
        public ?bool $pulse = null,
    ) {}

    /**
     * Build a state from any of the shorthand forms the API accepts.
     *
     * A bare string is ambiguous by design: 'Active' is a label but 'green' is
     * a colour. Resolving that by guessing would be fragile, so a bare string
     * is always a label — a colour-only state is written ['color' => 'green'].
     *
     * @param  array<string, mixed>|Stringable|string|int|float|bool|null  $definition
     */
    public static function make(mixed $definition, string|int|null $key = null): self
    {
        if ($definition instanceof self) {
            $definition->key ??= $key;

            return $definition;
        }

        if (is_array($definition)) {
            /** @var array<string, mixed> $definition */
            return new self(
                key: $key,
                label: self::stringOrNull($definition['label'] ?? null),
                color: self::colorOrNull($definition['color'] ?? null),
                icon: self::stringOrNull($definition['icon'] ?? null) ?: null,
                tooltip: self::stringOrNull($definition['tooltip'] ?? $definition['description'] ?? null) ?: null,
                pulse: isset($definition['pulse']) ? (bool) $definition['pulse'] : null,
            );
        }

        return new self(key: $key, label: self::stringOrNull($definition));
    }

    /**
     * Merge another state over this one, keeping values already set.
     *
     * Used when labels(), colors() and icons() are called separately on the
     * same field; each writes only its own slot.
     */
    public function mergeFrom(self $other): self
    {
        return new self(
            key: $this->key ?? $other->key,
            label: $this->label ?? $other->label,
            color: $this->color ?? $other->color,
            icon: $this->icon ?? $other->icon,
            tooltip: $this->tooltip ?? $other->tooltip,
            pulse: $this->pulse ?? $other->pulse,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Stringable || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @return Color|string|array<string, mixed>|null
     */
    private static function colorOrNull(mixed $value): Color|string|array|null
    {
        if ($value instanceof Color || is_array($value)) {
            return $value;
        }

        return self::stringOrNull($value);
    }
}
