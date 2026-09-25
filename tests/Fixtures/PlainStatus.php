<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures;

/** A backed enum implementing none of the contracts, to exercise the fallbacks. */
enum PlainStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case PublishedLate = 'publishedLate';
}
