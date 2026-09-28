<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

/**
 * The set of colour families and shades Nova actually emits at :root.
 *
 * This is the one class that knows what vendor/laravel/nova/generators.js
 * produces. Everything else asks it rather than hard-coding a palette, which is
 * how the package avoids the frozen-Tailwind-v1 problem that 1.x had.
 */
final class Palette
{
    /**
     * Families Nova emits with a full shade ramp.
     *
     * `primary` is Nova's alias for sky and `gray` its alias for slate; both are
     * emitted under their alias, so an application can re-theme them.
     *
     * @var list<string>
     */
    public const FAMILIES = [
        'primary', 'slate', 'gray', 'zinc', 'neutral', 'stone',
        'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald',
        'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple',
        'fuchsia', 'pink', 'rose',
    ];

    /**
     * Families whose palette entry is a flat string rather than a ramp, so they
     * emit `--colors-black` with no shade suffix.
     *
     * @var list<string>
     */
    public const SHADELESS_FAMILIES = ['black', 'white'];

    /** @var list<int> */
    public const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    /**
     * Membership is a hash lookup, not a scan.
     *
     * These three run once per resolved colour, and a linear scan over 23
     * families was the single hottest thing in the resolver on a wide index.
     *
     * @var array<string, true>
     */
    private const FAMILY_SET = [
        'primary' => true, 'slate' => true, 'gray' => true, 'zinc' => true,
        'neutral' => true, 'stone' => true, 'red' => true, 'orange' => true,
        'amber' => true, 'yellow' => true, 'lime' => true, 'green' => true,
        'emerald' => true, 'teal' => true, 'cyan' => true, 'sky' => true,
        'blue' => true, 'indigo' => true, 'violet' => true, 'purple' => true,
        'fuchsia' => true, 'pink' => true, 'rose' => true,
    ];

    /** @var array<string, true> */
    private const SHADELESS_SET = ['black' => true, 'white' => true];

    /** @var array<int, int> Shade => its index in SHADES. */
    private const SHADE_INDEX = [
        50 => 0, 100 => 1, 200 => 2, 300 => 3, 400 => 4, 500 => 5,
        600 => 6, 700 => 7, 800 => 8, 900 => 9, 950 => 10,
    ];

    public static function hasFamily(string $family): bool
    {
        return isset(self::FAMILY_SET[$family]) || isset(self::SHADELESS_SET[$family]);
    }

    public static function isShadeless(string $family): bool
    {
        return isset(self::SHADELESS_SET[$family]);
    }

    public static function hasShade(int $shade): bool
    {
        return isset(self::SHADE_INDEX[$shade]);
    }

    /**
     * Build the CSS value for one family/shade pair.
     *
     * Nova stores the channels only ("239, 68, 68"), so the variable has to be
     * wrapped in rgba() at the point of use rather than used bare.
     */
    public static function cssValue(string $family, ?int $shade): string
    {
        if ($shade === null || self::isShadeless($family)) {
            return sprintf('rgba(var(--colors-%s))', $family);
        }

        return sprintf('rgba(var(--colors-%s-%d))', $family, $shade);
    }

    /**
     * The dark-mode counterpart of a shade, one step lighter.
     *
     * Nova's dark surfaces are slate-800/900, so a mark has to lighten to keep
     * the same apparent weight. Shades that are already light stay put, and the
     * ends of the ramp clamp instead of running off it.
     */
    public static function lighten(int $shade): int
    {
        if ($shade <= 300) {
            return $shade;
        }

        // Anything above 300 is at index 4 or higher when it is on the ramp at
        // all, so the only miss to guard is a shade that is not a ramp stop.
        $index = self::SHADE_INDEX[$shade] ?? null;

        if ($index === null) {
            return $shade;
        }

        return self::SHADES[$index - 1];
    }
}
