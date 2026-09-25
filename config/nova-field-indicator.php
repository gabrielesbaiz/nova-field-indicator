<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Appearance defaults
    |--------------------------------------------------------------------------
    |
    | Applied to every indicator that does not override them on the field.
    | Shape accepts dot, ring, pill or square; size accepts sm, md or lg.
    | Measurements live in the stylesheet, so these are tokens, not pixels.
    |
    */

    'appearance' => [
        'shape' => 'dot',
        'size' => 'md',
        'inline' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    |
    | "empty" is rendered when a field resolves to no indicator at all. When
    | "translate" is on, every label from the state table is passed through
    | __(); Laravel returns the key unchanged if no entry exists, so this is a
    | no-op for an untranslated application. Turn it off per field with
    | ->withoutTranslation() when labels are user-generated.
    |
    */

    'labels' => [
        'empty' => '—',
        'translate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Colours
    |--------------------------------------------------------------------------
    |
    | Colours resolve to Nova's own palette variables (--colors-<family>-<shade>),
    | so re-theming Nova re-themes every indicator. "default" is used for a value
    | with no state of its own, and "shades" sets the light/dark pair chosen for
    | a bare family name such as "emerald".
    |
    | "tokens" is the rebranding hook: define a name once here instead of
    | repeating a literal at every call site.
    |
    |   'tokens' => [
    |       'brand' => ['light' => 'violet-600', 'dark' => 'violet-400'],
    |   ],
    |
    */

    'colors' => [
        'default' => 'gray',

        'shades' => ['light' => 500, 'dark' => 400],

        // Opacity, in percent, of the tint behind a pill-shaped indicator.
        'soft_alpha' => 15,

        'tokens' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | Icons are heroicons rendered by Nova's own <Icon> component, so they add
    | nothing to this package's JavaScript bundle. "defaults" supplies an icon
    | per resolved colour token, which is what makes a bare ->withIcons() useful
    | without an icon map.
    |
    */

    'icons' => [
        'enabled' => false,

        // solid, outline, mini or micro.
        'type' => 'solid',

        'defaults' => [
            'success' => 'check-circle',
            'danger' => 'x-circle',
            'warning' => 'exclamation-triangle',
            'info' => 'information-circle',
        ],
    ],

];
