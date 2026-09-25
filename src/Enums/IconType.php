<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Enums;

/**
 * Heroicon variants accepted by laravel-nova-ui's <Icon> component.
 *
 * Mirrors the `type` prop in vendor/laravel/nova/resources/js/fields/Index/BadgeField.vue.
 */
enum IconType: string
{
    case Solid = 'solid';
    case Outline = 'outline';
    case Mini = 'mini';
    case Micro = 'micro';
}
