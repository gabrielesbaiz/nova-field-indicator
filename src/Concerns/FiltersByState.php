<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Gabrielesbaiz\NovaFieldIndicator\Support\EnumStates;
use Illuminate\Support\Arr;
use Laravel\Nova\Fields\Filters\Filter;
use Laravel\Nova\Fields\Filters\SelectFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Index filtering, for free.
 *
 * Opt-in via ->filterable(); the filter also stays hidden when there are no
 * known options, so a field configured only with colorUsing() never renders an
 * empty dropdown. Note this is strictly better than Nova's own Badge, whose
 * filter options come solely from map() and are therefore empty unless you
 * happened to call it.
 */
trait FiltersByState
{
    /**
     * Prepare the field for the filter dropdown.
     *
     * Calls parent::jsonSerialize() rather than $this->jsonSerialize(), exactly
     * as Badge does: the filter needs the bare field, not a resolved indicator
     * for whatever value happened to be loaded.
     *
     * @return array<string, mixed>
     */
    public function serializeForFilter(): array
    {
        return transform(parent::jsonSerialize(), function (array $field): array {
            $options = [];

            foreach ($this->filterOptions() as $value) {
                $state = $this->stateFor($value);

                $options[] = [
                    'value' => $value,
                    'label' => $state?->label !== null
                        ? $this->translate((string) $state->label)
                        : (string) $value,
                ];
            }

            return array_merge(
                Arr::only($field, ['uniqueKey', 'name', 'attribute']),
                ['options' => $options],
            );
        });
    }

    protected function makeFilter(NovaRequest $request): ?Filter
    {
        return $this->filterOptions() === [] ? null : new SelectFilter($this);
    }

    /**
     * Every value the filter should offer, in declaration order.
     *
     * @return list<string|int>
     */
    protected function filterOptions(): array
    {
        $keys = $this->stateKeys();

        if ($keys === [] && ($enum = $this->enumClass()) !== null) {
            $keys = EnumStates::values($enum);
        }

        /** @var list<string|int> $keys */
        return array_values($keys);
    }
}
