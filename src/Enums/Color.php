<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Enums;

/**
 * The built-in colour tokens, mapped onto Nova 5's palette variables.
 *
 * Nova emits every Tailwind colour at :root as `--colors-<family>-<shade>`
 * holding an "r, g, b" body (see vendor/laravel/nova/generators.js), which is
 * why a token resolves to a family plus two shades rather than to a hex.
 *
 * This enum is where the headline 2.x bug is fixed. `success`, `danger`,
 * `warning` and `info` used to be styled as `var(--success)` and friends —
 * variables that existed in Nova 3/4 and do **not** exist in Nova 5, so those
 * four dots painted with an invalid colour and rendered invisible. They now
 * resolve to real palette families, matching Laravel\Nova\Badge::$types.
 *
 * The dark shade is generally one step lighter than the light shade: Nova's
 * dark surfaces are slate-800/900, where a 500 mark reads dimmer than it does
 * on white.
 */
enum Color: string
{
    // Semantic tokens. These four were the broken ones in 2.x.
    case Primary = 'primary';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';

    // Neutrals. Both spellings of grey are cases so 2.x configuration keeps working.
    //
    // Plain palette families (red, emerald, slate, ...) are deliberately NOT
    // cases. They are resolved as families instead, which is what lets the
    // configured default shades apply to them — enumerating them here would
    // pin every one of them to a hard-coded 500/400 pair.
    case Gray = 'gray';
    case Grey = 'grey';
    case Black = 'black';
    case White = 'white';

    /**
     * The Nova palette family this token draws from.
     *
     * Note `primary` is Nova's own alias for sky and `gray` is its alias for
     * slate, so both are addressed by their alias rather than their target —
     * that is what lets an application re-theme them.
     */
    public function family(): string
    {
        return match ($this) {
            self::Primary => 'primary',
            self::Success => 'green',
            self::Danger => 'red',
            self::Warning => 'yellow',
            self::Info => 'sky',
            self::Gray, self::Grey, self::Black => 'gray',
            self::White => 'white',
        };
    }

    /**
     * Shade used in light mode, or null for a shade-less family.
     *
     * `black` and `white` are flat strings in Nova's palette, so they emit
     * `--colors-white` with no shade suffix at all.
     */
    public function lightShade(): ?int
    {
        return match ($this) {
            self::White => null,
            self::Black => 900,
            self::Gray, self::Grey => 400,
            default => 500,
        };
    }

    /**
     * Shade used in dark mode.
     *
     * Black inverts rather than lightening by one step: a 900 mark on a
     * slate-900 surface would be invisible.
     */
    public function darkShade(): ?int
    {
        return match ($this) {
            self::White => null,
            self::Black => 100,
            self::Gray, self::Grey => 400,
            self::Warning => 300,
            default => 400,
        };
    }

    /**
     * The heroicon used for this token when ->withIcons() is on and the state
     * defines no icon of its own. Mirrors Laravel\Nova\Fields\Badge::$icons.
     */
    public function icon(): ?string
    {
        return match ($this) {
            self::Success => 'check-circle',
            self::Danger => 'x-circle',
            self::Warning => 'exclamation-triangle',
            self::Info, self::Primary => 'information-circle',
            default => null,
        };
    }
}
