<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

use Closure;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;
use Gabrielesbaiz\NovaFieldIndicator\Enums\IconType;
use Gabrielesbaiz\NovaFieldIndicator\Results\ResolvedColor;
use Gabrielesbaiz\NovaFieldIndicator\Support\State;

/**
 * Optional heroicon alongside the mark.
 *
 * Icons are free on the client: laravel-nova-ui is external in the Vite build,
 * so <Icon> resolves to Nova's already-loaded copy and the heroicon set costs
 * no bundle bytes. Icon names are deliberately not validated — Nova's Icon
 * renders nothing for an unknown name, and mirroring its 300-name list here
 * would be a maintenance tax with no payoff.
 */
trait ResolvesIcons
{
    protected ?bool $withIcons = null;

    protected ?IconType $iconType = null;

    protected ?Closure $iconCallback = null;

    /**
     * @return $this
     */
    public function withIcons(bool $with = true): static
    {
        $this->withIcons = $with;

        return $this;
    }

    /**
     * @return $this
     */
    public function iconType(IconType|string $type): static
    {
        $this->iconType = $type instanceof IconType
            ? $type
            : (IconType::tryFrom($type) ?? IconType::Solid);

        return $this;
    }

    /**
     * @param  Closure(mixed, mixed): (string|null)  $callback
     * @return $this
     */
    public function iconUsing(Closure $callback): static
    {
        $this->iconCallback = $callback;

        return $this->withIcons();
    }

    /**
     * @return array{name: string, type: string}|null
     */
    protected function resolveIconFor(?State $state, ResolvedColor $color, mixed $resource): ?array
    {
        if (! $this->iconsEnabled()) {
            return null;
        }

        $name = null;

        if ($this->iconCallback !== null) {
            $name = ($this->iconCallback)($this->value, $resource);
        } elseif ($state?->icon !== null) {
            $name = $state->icon;
        } elseif ($color->token !== null) {
            // With icons on but no map, fall back to the semantic icon for the
            // resolved token so ->withIcons() alone is already useful.
            $name = Color::tryFrom($color->token)?->icon()
                ?? $this->configuredIconFor($color->token);
        }

        if ($name === null || $name === '') {
            return null;
        }

        return ['name' => (string) $name, 'type' => $this->resolvedIconType()->value];
    }

    protected function iconsEnabled(): bool
    {
        return $this->withIcons ?? (bool) config('nova-field-indicator.icons.enabled', false);
    }

    protected function resolvedIconType(): IconType
    {
        if ($this->iconType instanceof IconType) {
            return $this->iconType;
        }

        $configured = config('nova-field-indicator.icons.type', 'solid');

        return is_string($configured)
            ? (IconType::tryFrom($configured) ?? IconType::Solid)
            : IconType::Solid;
    }

    protected function configuredIconFor(string $token): ?string
    {
        /** @var array<string, string> $defaults */
        $defaults = config('nova-field-indicator.icons.defaults', []);

        return is_array($defaults) && isset($defaults[$token]) ? (string) $defaults[$token] : null;
    }
}
