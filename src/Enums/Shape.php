<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Enums;

/**
 * The visual form the indicator mark takes.
 *
 * Every shape renders the same DOM node and is differentiated by a
 * `[data-shape]` attribute selector in resources/css/field.css, so adding a
 * shape never touches the Vue component.
 *
 * There is deliberately no `Bar` case: a bar implies a magnitude axis, which
 * needs a min/max domain and a width calculation. That is a progress field,
 * not an indicator.
 */
enum Shape: string
{
    case Dot = 'dot';
    case Ring = 'ring';
    case Pill = 'pill';
    case Square = 'square';

    /**
     * Whether the shape draws a separate mark element next to the label.
     *
     * The pill has no mark of its own — the tinted pill *is* the mark — so the
     * Vue component skips the glyph for it.
     */
    public function hasMark(): bool
    {
        return $this !== self::Pill;
    }
}
