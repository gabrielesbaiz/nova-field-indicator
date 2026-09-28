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
 * is serialized — see jsonSerialize(). 1.x pushed the whole label and colour
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
    use Concerns\ReadsConfiguration;
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
     * Renamed from 1.x's very generic `indicator-field`, which risked
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
     * The matched range band, memoized for the row being serialized.
     *
     * `false` is the "not looked up yet" marker, since `null` is a real answer
     * — the value fell outside every band, or ->ranges() is not in use. Nova
     * builds a new field per row, so the memo cannot outlive one value.
     */
    private State|null|false $rangeState = false;

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
            'emptyText' => (string) $this->setting('labels', 'empty', '—'),
        ]);
    }

    /**
     * Apply the display-only defaults.
     *
     * configureDefaults() rather than the constructor because that is the hook
     * Nova's own Badge uses, and it runs after the field's attribute is known.
     * exceptOnForms() replaces 1.x's $showOnCreation/$showOnUpdate properties
     * and additionally covers the attach and update-attached views, which those
     * two properties missed.
     *
     * The hook lands in Nova 5.11, which is why composer.json requires ^5.11
     * and conflicts anything below it. #[Override] is deliberate: on an older
     * Nova the class fails to load, instead of loading fine, never running this
     * method, and quietly showing the field on every form.
     */
    #[Override]
    protected function configureDefaults(): void
    {
        parent::configureDefaults();

        $this->exceptOnForms();

        // Nova declares $inline itself and serializes it, so the package
        // default is applied to its property rather than shadowing it.
        $this->inline = (bool) $this->setting('appearance', 'inline', true);
    }

    /**
     * Resolve one value into a fully described indicator.
     *
     * Override this in a subclass to change resolution; it is the only seam the
     * rest of the field depends on.
     */
    protected function resolveIndicatorFor(string|int|null $key, mixed $resource): Indicator
    {
        // Matched once and passed down. resolveColorFor() used to run the
        // threshold scan a second time for the same value.
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

        // $keys is already a list, so array_map returns one — no array_values.
        return array_map(
            fn (string|int|null $key): array => $this->resolveIndicatorFor($key, $this->resource)->toArray(),
            $keys,
        );
    }

    /**
     * The range band matching the current value, if ->ranges() is in use.
     */
    protected function rangeStateFor(): ?State
    {
        if ($this->rangeState !== false) {
            return $this->rangeState;
        }

        return $this->rangeState = $this->ranges?->match($this->value);
    }
}
