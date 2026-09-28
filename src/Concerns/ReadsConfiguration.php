<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Concerns;

/**
 * One configuration read per field, instead of one per question asked.
 *
 * Serializing a row asks for the shape, the size, the empty label, the default
 * colour, the token table, the shades, the soft alpha, whether icons are on,
 * which icon type, the icon defaults and whether labels translate. Each
 * `config()` call resolves the repository out of the container and walks a
 * dotted path, and Nova rebuilds the field for every row — so a 50-row index
 * with three indicator columns was making well over a thousand of them.
 *
 * The whole package namespace is a small array. Read it once, keep it on the
 * instance, and index into it directly.
 */
trait ReadsConfiguration
{
    /** @var array<string, mixed>|null */
    private ?array $packageConfig = null;

    /**
     * @return array<string, mixed>
     */
    protected function packageConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = $this->packageConfig ??= (array) config('nova-field-indicator', []);

        return $config;
    }

    /**
     * One value from a group, without walking a dotted string.
     */
    protected function setting(string $group, string $key, mixed $default = null): mixed
    {
        $config = $this->packageConfig();

        if (! isset($config[$group]) || ! is_array($config[$group])) {
            return $default;
        }

        return $config[$group][$key] ?? $default;
    }
}
