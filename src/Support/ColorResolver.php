<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;
use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;
use Gabrielesbaiz\NovaFieldIndicator\Results\ResolvedColor;

/**
 * Turns everything the fluent API accepts as a colour into finished CSS.
 *
 * Resolution happens here, on the server, exactly once per value — not per row
 * in the browser. Rule 7 is also the package's CSS-injection boundary: the
 * resolved string is applied as an inline custom property, so a literal that
 * could carry `url(` or close a declaration must never survive this class.
 */
final class ColorResolver
{
    /**
     * Anchored allow-list for literal CSS colours.
     *
     * Deliberately strict. The function bodies accept only characters that can
     * appear in a colour — no parens, so a nested `url(` cannot hide inside
     * one — and var() accepts an identifier with an optional literal fallback.
     */
    private const LITERAL_PATTERN = '/^(?:
        \#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})
        | (?:rgb|rgba|hsl|hsla|oklch|lab|lch)\([0-9a-z.,%\/\s+-]*\)
        | color-mix\(\s*in\s+[a-z-]+\s*,[0-9a-z.,%\/\s()+-]*\)
        | var\(\s*--[a-z0-9_-]+\s*(?:,\s*[#0-9a-z.,%\/\s()+-]*)?\)
    )$/ix';

    /** Substrings that can never legitimately appear in a colour literal. */
    private const FORBIDDEN = [';', '{', '}', '/*', '*/', '\\', '<', '>', 'url(', 'expression(', 'image-set(', 'javascript:'];

    private const MAX_LITERAL_LENGTH = 64;

    /**
     * @param  array<string, mixed>  $tokens  Application tokens from config.
     * @param  array{light: int, dark: int}  $shades  Default shades for a bare family.
     */
    public function __construct(
        private array $tokens = [],
        private array $shades = ['light' => 500, 'dark' => 400],
        private int $softAlpha = 15,
    ) {}

    /**
     * @param  Color|string|array<string, mixed>|null  $color
     */
    public function resolve(Color|string|array|null $color, ?Color $fallback = null): ResolvedColor
    {
        $fallback ??= Color::Gray;

        if ($color === null || $color === '') {
            return $this->fromToken($fallback);
        }

        // Rule 1 — a Color case.
        if ($color instanceof Color) {
            return $this->fromToken($color);
        }

        // Rule 6 — an explicit light/dark pair, each side resolved on its own.
        if (is_array($color)) {
            return $this->fromPair($color, $fallback);
        }

        $value = trim($color);

        // Application tokens come first, so config is a genuine override hook:
        // an app can rebrand `success` or shadow a palette family in one place
        // rather than at every call site.
        if ($this->hasApplicationToken($value)) {
            return $this->fromApplicationToken($value, $fallback);
        }

        // A built-in semantic or neutral token.
        if ($token = Color::tryFrom(strtolower($value))) {
            return $this->fromToken($token);
        }

        // Rule 3 — "family-shade".
        if (preg_match('/^([a-z]+)-(\d{2,3})$/i', $value, $matches) === 1) {
            return $this->fromFamilyShade(strtolower($matches[1]), (int) $matches[2], $value);
        }

        // Rule 4 — a bare Tailwind family at the configured default shades.
        if (Palette::hasFamily(strtolower($value))) {
            return $this->fromFamily(strtolower($value), $value);
        }

        // Rule 7 — a literal CSS colour, if it survives the allow-list.
        if ($this->looksLikeLiteral($value)) {
            $literal = $this->assertSafeLiteral($value);

            return new ResolvedColor($literal, $literal, $this->soften($literal));
        }

        // Rule 8.
        throw IndicatorException::unknownColorToken($value);
    }

    public function fromToken(Color $token): ResolvedColor
    {
        $family = $token->family();

        return new ResolvedColor(
            light: Palette::cssValue($family, $token->lightShade()),
            dark: Palette::cssValue($family, $token->darkShade()),
            soft: $this->soften(Palette::cssValue($family, $token->lightShade())),
            token: $token->value,
        );
    }

    /**
     * Wrap a resolved colour in a translucent mix for the pill background.
     *
     * color-mix() is what makes one colour variable produce both a tint and a
     * readable foreground; the stylesheet carries an @supports fallback for
     * engines that lack it.
     */
    private function soften(string $value): string
    {
        return sprintf('color-mix(in srgb, %s %d%%, transparent)', $value, $this->softAlpha);
    }

    /**
     * @param  array<string, mixed>  $color
     */
    private function fromPair(array $color, Color $fallback): ResolvedColor
    {
        /** @var Color|string|array<string, mixed>|null $light */
        $light = $color['light'] ?? null;
        /** @var Color|string|array<string, mixed>|null $dark */
        $dark = $color['dark'] ?? null;

        $resolvedLight = $this->resolve($light, $fallback);

        // A pair with no dark side keeps the light value in both themes, which
        // is the right default for a literal: we cannot guess a brand's dark
        // variant, and silently shifting it would be worse than leaving it.
        $resolvedDark = $dark === null ? $resolvedLight : $this->resolve($dark, $fallback);

        return new ResolvedColor(
            light: $resolvedLight->light,
            dark: $resolvedDark->light,
            soft: $resolvedLight->soft,
            token: null,
        );
    }

    private function hasApplicationToken(string $value): bool
    {
        return array_key_exists(strtolower($value), array_change_key_case($this->tokens, CASE_LOWER));
    }

    private function fromApplicationToken(string $value, Color $fallback): ResolvedColor
    {
        /** @var Color|string|array<string, mixed>|null $definition */
        $definition = array_change_key_case($this->tokens, CASE_LOWER)[strtolower($value)];

        $resolved = $this->resolve($definition, $fallback);

        return new ResolvedColor(
            light: $resolved->light,
            dark: $resolved->dark,
            soft: $resolved->soft,
            token: strtolower($value),
        );
    }

    private function fromFamilyShade(string $family, int $shade, string $original): ResolvedColor
    {
        if (! Palette::hasFamily($family)) {
            throw IndicatorException::unknownColorToken($original);
        }

        if (! Palette::hasShade($shade)) {
            throw IndicatorException::unknownShade($family, $shade);
        }

        // An explicit shade is an explicit intent, so the dark side only moves
        // when it would otherwise be too dark to read on a slate-800 surface.
        $light = Palette::cssValue($family, $shade);

        return new ResolvedColor(
            light: $light,
            dark: Palette::cssValue($family, Palette::lighten($shade)),
            soft: $this->soften($light),
            token: $original,
        );
    }

    private function fromFamily(string $family, string $original): ResolvedColor
    {
        if (Palette::isShadeless($family)) {
            $value = Palette::cssValue($family, null);

            return new ResolvedColor($value, $value, $this->soften($value), $original);
        }

        $light = Palette::cssValue($family, $this->shades['light']);

        return new ResolvedColor(
            light: $light,
            dark: Palette::cssValue($family, $this->shades['dark']),
            soft: $this->soften($light),
            token: $original,
        );
    }

    /**
     * A cheap shape test, so a plain typo still reaches the "unknown token"
     * error rather than the harsher "unsafe literal" one.
     */
    private function looksLikeLiteral(string $value): bool
    {
        return str_starts_with($value, '#') || str_contains($value, '(');
    }

    private function assertSafeLiteral(string $value): string
    {
        if (strlen($value) > self::MAX_LITERAL_LENGTH) {
            throw IndicatorException::unsafeColorLiteral($value);
        }

        $lowered = strtolower($value);

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($lowered, $needle)) {
                throw IndicatorException::unsafeColorLiteral($value);
            }
        }

        if (preg_match(self::LITERAL_PATTERN, $value) !== 1) {
            throw IndicatorException::unsafeColorLiteral($value);
        }

        return $value;
    }
}
