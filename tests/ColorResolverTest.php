<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;
use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;
use Gabrielesbaiz\NovaFieldIndicator\Support\ColorResolver;
use Gabrielesbaiz\NovaFieldIndicator\Support\Palette;

/*
 * The four tokens below are the reason 3.0 exists. In 2.x they were styled as
 * var(--success) and friends, which Nova 5 does not define, so the dot painted
 * with an invalid colour and rendered invisible.
 */
it('resolves the four semantic tokens that were invisible on Nova 5', function (string $token, string $family) {
    $color = (new ColorResolver)->resolve($token);

    expect($color->light)->toBe("rgba(var(--colors-{$family}-500))")
        ->and($color->light)->not->toContain("var(--{$token})");
})->with([
    ['success', 'green'],
    ['danger', 'red'],
    ['warning', 'yellow'],
    ['info', 'sky'],
]);

it('gives every built-in token a legal family and shade', function () {
    $resolver = new ColorResolver;

    foreach (Color::cases() as $case) {
        $color = $resolver->resolve($case);

        expect(Palette::hasFamily($case->family()))->toBeTrue()
            ->and($color->light)->toStartWith('rgba(var(--colors-')
            ->and($color->dark)->toStartWith('rgba(var(--colors-')
            ->and($color->token)->toBe($case->value);

        if ($case->lightShade() !== null) {
            expect(Palette::hasShade($case->lightShade()))->toBeTrue();
        }
    }
});

it('keeps both spellings of grey working', function () {
    $resolver = new ColorResolver;

    expect($resolver->resolve('grey')->light)->toBe($resolver->resolve('gray')->light);
});

it('drops the shade suffix for shade-less families', function () {
    expect((new ColorResolver)->resolve('white')->light)->toBe('rgba(var(--colors-white))');
});

it('inverts black rather than lightening it by one step', function () {
    // A 900 mark on a slate-900 surface would be invisible.
    $color = (new ColorResolver)->resolve('black');

    expect($color->light)->toBe('rgba(var(--colors-gray-900))')
        ->and($color->dark)->toBe('rgba(var(--colors-gray-100))');
});

it('resolves a family-shade pair and lightens the dark side', function () {
    $color = (new ColorResolver)->resolve('green-600');

    expect($color->light)->toBe('rgba(var(--colors-green-600))')
        ->and($color->dark)->toBe('rgba(var(--colors-green-500))');
});

it('clamps the dark shade at both ends of the ramp', function () {
    $resolver = new ColorResolver;

    // Already light enough to read on a dark surface, so it stays put.
    expect($resolver->resolve('sky-50')->dark)->toBe('rgba(var(--colors-sky-50))')
        ->and($resolver->resolve('sky-300')->dark)->toBe('rgba(var(--colors-sky-300))')
        ->and($resolver->resolve('sky-950')->dark)->toBe('rgba(var(--colors-sky-900))');
});

it('resolves a bare Tailwind family at the default shades', function () {
    $color = (new ColorResolver)->resolve('emerald');

    expect($color->light)->toBe('rgba(var(--colors-emerald-500))')
        ->and($color->dark)->toBe('rgba(var(--colors-emerald-400))');
});

it('honours configured default shades', function () {
    $color = (new ColorResolver(shades: ['light' => 700, 'dark' => 200]))->resolve('emerald');

    expect($color->light)->toBe('rgba(var(--colors-emerald-700))')
        ->and($color->dark)->toBe('rgba(var(--colors-emerald-200))');
});

it('resolves an application token recursively', function () {
    $resolver = new ColorResolver(tokens: [
        'brand' => ['light' => 'violet-600', 'dark' => 'violet-400'],
    ]);

    $color = $resolver->resolve('brand');

    expect($color->light)->toBe('rgba(var(--colors-violet-600))')
        ->and($color->dark)->toBe('rgba(var(--colors-violet-400))')
        ->and($color->token)->toBe('brand');
});

it('lets an application token shadow a palette family name', function () {
    $resolver = new ColorResolver(tokens: ['teal' => '#008080']);

    expect($resolver->resolve('teal')->light)->toBe('#008080');
});

it('resolves an explicit light and dark pair', function () {
    $color = (new ColorResolver)->resolve(['light' => '#111111', 'dark' => '#eeeeee']);

    expect($color->light)->toBe('#111111')
        ->and($color->dark)->toBe('#eeeeee');
});

it('keeps a literal identical in both themes when no dark side is given', function () {
    $color = (new ColorResolver)->resolve('#7c3aed');

    expect($color->dark)->toBe($color->light);
});

it('accepts every supported literal colour form', function (string $literal) {
    expect((new ColorResolver)->resolve($literal)->light)->toBe($literal);
})->with([
    '#fff',
    '#ffff',
    '#22c55e',
    '#aabbccdd',
    'rgb(1 2 3)',
    'rgba(1,2,3,.5)',
    'hsl(210 40% 50%)',
    'hsla(210, 40%, 50%, .5)',
    'oklch(0.7 0.1 200)',
    'var(--brand)',
    'var(--brand, #fff)',
]);

/*
 * The resolved value is applied as an inline custom property, so a literal that
 * could close a declaration or smuggle a url() must never survive this class.
 * 2.x classified colours in the Vue component with an unanchored regex, which
 * would have passed most of these straight through into a style attribute.
 */
it('rejects CSS injection payloads', function (string $payload) {
    expect(fn () => (new ColorResolver)->resolve($payload))
        ->toThrow(IndicatorException::class);
})->with([
    '#fff; background-image:url(//evil)',
    'url(//evil)',
    'expression(alert(1))',
    'red}body{display:none',
    'var(--x); --y: url(z)',
    '</style><script>alert(1)</script>',
    'image-set("x.png")',
    'rgb(/* comment */ 1 2 3)',
    'var(--a)\\',
]);

it('rejects a literal longer than the cap', function () {
    expect(fn () => (new ColorResolver)->resolve('#'.str_repeat('a', 90)))
        ->toThrow(IndicatorException::class);
});

it('rejects hex lengths that are not valid CSS', function (string $hex) {
    expect(fn () => (new ColorResolver)->resolve($hex))->toThrow(IndicatorException::class);
})->with(['#12345', '#1234567', '#ff']);

it('throws for an unknown token spelling', function () {
    // A typo in a resource is developer error and should surface immediately,
    // unlike an unknown row *value*, which falls back silently.
    expect(fn () => (new ColorResolver)->resolve('blurple'))
        ->toThrow(IndicatorException::class);
});

it('throws for an unknown shade on a known family', function () {
    expect(fn () => (new ColorResolver)->resolve('green-450'))
        ->toThrow(IndicatorException::class);
});

it('falls back to the given token when the colour is null', function () {
    $color = (new ColorResolver)->resolve(null, Color::Danger);

    expect($color->token)->toBe('danger');
});

it('builds a soft tint from the light value', function () {
    $color = (new ColorResolver(softAlpha: 20))->resolve('success');

    expect($color->soft)->toBe('color-mix(in srgb, rgba(var(--colors-green-500)) 20%, transparent)');
});
