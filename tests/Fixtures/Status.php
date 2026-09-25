<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures;

use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasColor;
use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasIcon;
use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasLabel;
use Gabrielesbaiz\NovaFieldIndicator\Enums\Color;

/** A self-describing enum: implements all three contracts. */
enum Status: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Banned = 'banned';
    case Invited = 'invited';

    public function indicatorLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Banned => 'Banned',
            self::Invited => 'Invited',
        };
    }

    public function indicatorColor(): Color|string|array|null
    {
        return match ($this) {
            self::Active => Color::Success,
            self::Banned => 'danger',
            self::Invited => 'blue',
        };
    }

    public function indicatorIcon(): ?string
    {
        return match ($this) {
            self::Active => 'check-circle',
            self::Banned => 'x-circle',
            self::Invited => 'envelope',
        };
    }
}
