<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Exceptions;

use RuntimeException;

/**
 * Thrown for developer error at field-definition time only.
 *
 * An unknown *value* — a row whose status is missing from the state table — is
 * data, never an exception: it falls back to the default colour and label so an
 * index never dies over one odd row. An unknown *token spelling* is a typo in
 * the resource and throws immediately, which is the opposite of Nova's own
 * Badge field (it throws for both).
 */
class IndicatorException extends RuntimeException
{
    public static function unknownColorToken(string $token): self
    {
        return new self(sprintf(
            'Unknown indicator colour [%s]. Use a built-in token (success, danger, warning, info, '
            .'primary, or a Tailwind family), a "family-shade" pair such as "emerald-600", a '
            .'["light" => ..., "dark" => ...] pair, or a literal CSS colour.',
            $token,
        ));
    }

    public static function unsafeColorLiteral(string $value): self
    {
        return new self(sprintf(
            'The colour literal [%s] was rejected. Literals are limited to #hex, rgb()/rgba(), '
            .'hsl()/hsla(), oklch(), color-mix() and var(--custom-property); they may not contain '
            .'";", "{", "}", "url(", "expression(" or comments, because the value is applied as an '
            .'inline custom property.',
            $value,
        ));
    }

    public static function unknownShade(string $family, int $shade): self
    {
        return new self(sprintf(
            'Shade [%d] does not exist for the Nova palette family [%s]. Valid shades are '
            .'50, 100, 200, 300, 400, 500, 600, 700, 800, 900 and 950.',
            $shade,
            $family,
        ));
    }

    public static function enumNotBacked(string $enum): self
    {
        return new self(sprintf(
            'The enum [%s] is not a backed enum. NovaFieldIndicator needs a string- or int-backed '
            .'enum so it can match a stored attribute value against a case.',
            $enum,
        ));
    }

    public static function notAnEnum(string $class): self
    {
        return new self(sprintf('[%s] is not an enum.', $class));
    }

    public static function invalidRangeKey(mixed $key): self
    {
        return new self(sprintf(
            'Range keys must be numeric lower bounds; [%s] is not.',
            is_scalar($key) ? (string) $key : get_debug_type($key),
        ));
    }
}
