<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Results;

use Illuminate\Contracts\Support\Arrayable;

/**
 * One fully resolved indicator, ready to serialize.
 *
 * Everything the browser needs and nothing it does not: no label map, no colour
 * map, no lookup. On a 50-row index 1.x re-sent the entire configuration per
 * row; this object is what replaces it.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Indicator implements Arrayable
{
    /**
     * @param  array{name: string, type: string}|null  $icon
     */
    public function __construct(
        public string|int|null $value,
        public ?string $label,
        public string $ariaLabel,
        public ResolvedColor $color,
        public ?array $icon = null,
        public ?string $tooltip = null,
        public bool $pulse = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            // Null when ->withoutLabel() is on; the mark then carries meaning
            // alone, which is exactly why ariaLabel below is non-nullable.
            'label' => $this->label,
            'ariaLabel' => $this->ariaLabel,
            'tooltip' => $this->tooltip,
            'color' => $this->color->toArray(),
            'icon' => $this->icon,
            'pulse' => $this->pulse,
        ];
    }
}
