<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Contracts;

interface HasIcon
{
    public function indicatorIcon(): ?string;
}
