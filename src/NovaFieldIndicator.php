<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator;

use Gabrielesbaiz\NovaFieldIndicator\Results\Indicator;
use Gabrielesbaiz\NovaFieldIndicator\Support\State;
use Gabrielesbaiz\NovaFieldIndicator\Support\ValueNormalizer;
use Laravel\Nova\Contracts\FilterableField;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FieldFilterable;
use Laravel\Nova\Fields\Unfillable;
use Override;

/**
 * A colour-coded status indicator for Nova index and detail views.
 *
 * Deliberately not final: subclassing is the documented extension point, and
 * resolveIndicatorFor() is the seam to override.
 *
 * Every lookup happens here on the server, once per value, and only the result
 * is serialized — see jsonSerialize(). 2.x pushed the whole label and colour
 * configuration to the browser with every row, then looked it up again per
 * cell.
 *
 * @phpstan-consistent-constructor
 */
class NovaFieldIndicator extends Field implements FilterableField, Unfillable
{
    use Concerns\DerivesFromEnums;
    use Concerns\FiltersByState;
    use Concerns\HasAppearance;
    use Concerns\HasStates;
    use Concerns\HidesValues;
    use Concerns\ResolvesColors;
    use Concerns\ResolvesIcons;
    use Concerns\ResolvesLabels;
    use FieldFilterable {
        // Both traits define serializeForFilter(). Nova's returns the whole
        // field; ours trims it to the option list a SelectFilter needs, so it
        // is the one that must win.
        Concerns\FiltersByState::serializeForFilter insteadof FieldFilterable;
    }

    /**
     * The field's Vue component.
     *
     * Renamed from 2.x's very generic `indicator-field`, which risked
     * colliding with any other package that registered the same name.
     *
     * @var string
     */
    public $component = 'nova-field-indicator';

    /**
     * @var string
     */
    public $textAlign = 'left';

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        $this->detectEnumFrom($this->value);

        $hidden = $this->shouldHideValue($this->value, $this->resource);

        return array_merge(parent::jsonSerialize(), [
            // Always a list. A scalar yields one entry, an array yields many,
            // so a JSON-cast tag column renders several marks with no extra
            // branching on the client.
            'indicators' => $hidden ? [] : $this->resolveIndicators(),
            'shape' => $this->resolvedShape()->value,
            'size' => $this->resolvedSize()->value,
            'shouldHide' => $hidden,
            'emptyText' => (string) config('nova-field-indicator.labels.empty', '—'),
        ]);
    }

    /**
     * Apply the display-only defaults.
     *
     * configureDefaults() rather than the constructor because that is the hook
     * Nova's own Badge uses, and it runs after the field's attribute is known.
     * exceptOnForms() replaces 2.x's $showOnCreation/$showOnUpdate properties
     * and additionally covers the attach and update-attached views, which those
     * two properties missed.
     */
    #[Override]
    protected function configureDefaults(): void
    {
        parent::configureDefaults();

        $this->exceptOnForms();

        // Nova declares $inline itself and serializes it, so the package
        // default is applied to its property rather than shadowing it.
        $this->inline = (bool) config('nova-field-indicator.appearance.inline', true);
    }

    /**
     * Resolve one value into a fully described indicator.
     *
     * Override this in a subclass to change resolution; it is the only seam the
     * rest of the field depends on.
     */
    protected function resolveIndicatorFor(string|int|null $key, mixed $resource): Indicator
    {
        $state = $this->stateFor($key) ?? $this->rangeStateFor();
        $color = $this->resolveColorFor($key, $state, $resource);

        return new Indicator(
            value: $key,
            label: $this->resolveLabelFor($key, $state, $resource),
            ariaLabel: $this->resolveAriaLabel($key, $state, $resource),
            color: $color,
            icon: $this->resolveIconFor($state, $color, $resource),
            tooltip: $this->resolveTooltipFor($state, $resource),
            pulse: $this->resolvePulseFor($key, $state, $resource),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function resolveIndicators(): array
    {
        $keys = ValueNormalizer::list($this->value);

        if ($keys === []) {
            // No value at all still resolves one indicator, so an ->unknown()
            // label and its colour are shown rather than an empty cell.
            $keys = [null];
        }

        return array_values(array_map(
            fn (string|int|null $key): array => $this->resolveIndicatorFor($key, $this->resource)->toArray(),
            $keys,
        ));
    }

    /**
     * The range band matching the current value, if ->ranges() is in use.
     */
    protected function rangeStateFor(): ?State
    {
        return $this->ranges?->match($this->value);
    }
}
