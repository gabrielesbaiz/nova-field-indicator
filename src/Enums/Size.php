<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Enums;

/**
 * Indicator scale.
 *
 * Serialized as a token only; the CSS owns the actual measurements as custom
 * property bundles on `[data-size]`, so the payload never carries pixels.
 */
enum Size: string
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';
}
