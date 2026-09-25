<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Gabrielesbaiz\NovaFieldIndicator\Support\State;
use Gabrielesbaiz\NovaFieldIndicator\Support\ValueNormalizer;

/**
 * The state table: how each possible attribute value should look.
 *
 * states() sets everything at once; labels(), colors(), icons() and
 * descriptions() each write a single slot and merge. All five funnel through
 * State::make(), so the rest of the field only ever reads one shape.
 */
trait HasStates
{
    /** @var array<array-key, State> */
    protected array $states = [];

    /**
     * Configure every value in one call.
     *
     * @param  array<array-key, mixed>  $states
     * @return $this
     */
    public function states(array $states): static
    {
        foreach ($states as $key => $definition) {
            $this->mergeState($key, State::make($definition, $key));
        }

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $labels
     * @return $this
     */
    public function labels(array $labels): static
    {
        foreach ($labels as $key => $label) {
            $this->mergeState($key, State::make(['label' => $label], $key));
        }

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $colors
     * @return $this
     */
    public function colors(array $colors): static
    {
        foreach ($colors as $key => $color) {
            $this->mergeState($key, State::make(['color' => $color], $key));
        }

        return $this;
    }

    /**
     * Setting an icon map implies wanting icons rendered.
     *
     * @param  array<array-key, mixed>  $icons
     * @return $this
     */
    public function icons(array $icons): static
    {
        foreach ($icons as $key => $icon) {
            $this->mergeState($key, State::make(['icon' => $icon], $key));
        }

        return $this->withIcons();
    }

    /**
     * @param  array<array-key, mixed>  $descriptions
     * @return $this
     */
    public function descriptions(array $descriptions): static
    {
        foreach ($descriptions as $key => $tooltip) {
            $this->mergeState($key, State::make(['tooltip' => $tooltip], $key));
        }

        return $this;
    }

    /**
     * The configured keys, used to build the filter option list.
     *
     * @return list<array-key>
     */
    public function stateKeys(): array
    {
        return array_keys($this->states);
    }

    protected function stateFor(string|int|null $key): ?State
    {
        if ($key === null) {
            return null;
        }

        return $this->states[$key] ?? null;
    }

    /**
     * Merge a state *under* whatever is already configured.
     *
     * Used by the enum-derived table, where hand-written labels() or colors()
     * on the same field must keep winning.
     */
    protected function mergeStateBeneath(mixed $key, State $state): void
    {
        $normalized = ValueNormalizer::key($key);

        if ($normalized === null) {
            return;
        }

        $this->states[$normalized] = isset($this->states[$normalized])
            ? $this->states[$normalized]->mergeFrom($state)
            : $state;
    }

    protected function mergeState(mixed $key, State $state): void
    {
        $normalized = ValueNormalizer::key($key);

        if ($normalized === null) {
            return;
        }

        $this->states[$normalized] = isset($this->states[$normalized])
            ? $state->mergeFrom($this->states[$normalized])
            : $state;
    }
}
