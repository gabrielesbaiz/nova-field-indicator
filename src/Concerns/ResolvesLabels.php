<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Closure;
use Gabrielesbaiz\NovaFieldIndicator\Support\State;

/**
 * Label resolution, including the accessible name.
 *
 * Note there is no withoutLabels() here. In 2.x that method meant "print the
 * raw value instead of a mapped label", which is now simply the default: an
 * unmapped value falls back to itself, the same way Nova's Badge behaves. The
 * method people actually wanted — a mark with no text at all — is
 * withoutLabel(), singular.
 */
trait ResolvesLabels
{
    protected ?string $unknownLabel = null;

    protected ?Closure $labelCallback = null;

    protected bool $showLabel = true;

    protected ?bool $translateLabels = null;

    /**
     * Label shown for a value that is not in the state table.
     *
     * @return $this
     */
    public function unknown(string $label, mixed $color = null): static
    {
        $this->unknownLabel = $label;

        if ($color !== null) {
            $this->defaultColor($color);
        }

        return $this;
    }

    /**
     * @param  Closure(mixed, mixed): (string|null)  $callback
     * @return $this
     */
    public function labelUsing(Closure $callback): static
    {
        $this->labelCallback = $callback;

        return $this;
    }

    /**
     * Render the mark only, with no visible text.
     *
     * The indicator then conveys meaning through colour alone, so the resolved
     * aria-label becomes its only accessible name — which is why
     * resolveAriaLabel() below can never return an empty string.
     *
     * @return $this
     */
    public function withoutLabel(bool $without = true): static
    {
        $this->showLabel = ! $without;

        return $this;
    }

    /**
     * Stop passing labels through __().
     *
     * Worth reaching for when labels are user-generated and might collide with
     * a translation key.
     *
     * @return $this
     */
    public function withoutTranslation(bool $without = true): static
    {
        $this->translateLabels = ! $without;

        return $this;
    }

    protected function resolveLabelFor(string|int|null $key, ?State $state, mixed $resource): ?string
    {
        if (! $this->showLabel) {
            return null;
        }

        return $this->resolveLabelText($key, $state, $resource);
    }

    /**
     * The accessible name. Never null, never empty.
     */
    protected function resolveAriaLabel(string|int|null $key, ?State $state, mixed $resource): string
    {
        $label = $this->resolveLabelText($key, $state, $resource);

        if ($label !== null && $label !== '') {
            // With no visible text the mark needs the field name for context,
            // since a bare "Active" in a column of dots says little.
            return $this->showLabel
                ? $label
                : trim(sprintf('%s: %s', $this->name, $label));
        }

        return (string) $this->name;
    }

    protected function resolveLabelText(string|int|null $key, ?State $state, mixed $resource): ?string
    {
        if ($this->labelCallback !== null) {
            // A closure owns its own translation; passing its result through
            // __() again would be surprising.
            $label = ($this->labelCallback)($this->value, $resource);

            return $label === null ? null : (string) $label;
        }

        if ($state?->label !== null) {
            return $this->translate((string) $state->label);
        }

        // Checked before the null guard: a field with no value at all is
        // exactly the case ->unknown() exists for.
        if ($this->unknownLabel !== null) {
            return $this->translate($this->unknownLabel);
        }

        if ($key === null) {
            return null;
        }

        // Fall back to the raw value, matching Badge.
        return (string) $key;
    }

    protected function translate(string $label): string
    {
        $enabled = $this->translateLabels
            ?? (bool) config('nova-field-indicator.labels.translate', true);

        if (! $enabled) {
            return $label;
        }

        // __() returns the key untouched when no entry exists, so this is a
        // no-op for an untranslated application.
        return (string) __($label);
    }
}
