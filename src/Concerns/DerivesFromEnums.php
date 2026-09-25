<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use BackedEnum;
use Gabrielesbaiz\NovaFieldIndicator\Support\EnumStates;

/**
 * Enum-first configuration.
 *
 * ->enum() collapses three parallel arrays into the enum the application
 * already owns. The better half is automatic: when the attribute value *is* a
 * BackedEnum, the state table is derived from its class on the fly, so a field
 * over an enum-cast column needs no configuration at all.
 */
trait DerivesFromEnums
{
    /** @var class-string|null */
    protected ?string $enum = null;

    protected bool $enumAutoDetected = false;

    /**
     * @param  class-string  $enum
     * @return $this
     */
    public function enum(string $enum): static
    {
        $this->enum = $enum;

        // Explicit states win, so the derived table goes underneath whatever
        // the developer already configured by hand.
        foreach (EnumStates::for($enum) as $key => $state) {
            $this->mergeStateBeneath($key, $state);
        }

        return $this;
    }

    /**
     * A ready-made true/false state table.
     *
     * @return $this
     */
    public function boolean(?string $true = null, ?string $false = null): static
    {
        return $this->states([
            'true' => [
                'label' => $true ?? __('Yes'),
                'color' => 'success',
                'icon' => 'check-circle',
            ],
            'false' => [
                'label' => $false ?? __('No'),
                'color' => 'gray',
                'icon' => 'x-circle',
            ],
        ]);
    }

    /**
     * Derive the state table from the value's own enum class, once.
     *
     * Called during resolution rather than configuration because the value is
     * not known until a row is being serialized.
     */
    protected function detectEnumFrom(mixed $value): void
    {
        if ($this->enum !== null || $this->enumAutoDetected) {
            return;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (! $value instanceof BackedEnum) {
            return;
        }

        $this->enumAutoDetected = true;

        foreach (EnumStates::for($value::class) as $key => $state) {
            $this->mergeStateBeneath($key, $state);
        }
    }

    /**
     * @return class-string|null
     */
    protected function enumClass(): ?string
    {
        return $this->enum;
    }
}
