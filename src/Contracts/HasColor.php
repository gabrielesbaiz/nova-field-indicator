<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Contracts;

use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;

interface HasColor
{
    /**
     * @return Color|string|array<string, mixed>|null
     */
    public function indicatorColor(): Color|string|array|null;
}
