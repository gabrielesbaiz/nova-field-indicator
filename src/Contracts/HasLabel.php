<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Contracts;

/**
 * Lets an enum case name itself.
 *
 * Named indicatorLabel() rather than getLabel() on purpose: an application enum
 * can implement Filament's contracts and these side by side without a clash.
 */
interface HasLabel
{
    public function indicatorLabel(): ?string;
}
