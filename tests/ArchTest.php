<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Illuminate\Contracts\Support\Arrayable;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('it declares strict types everywhere')
    ->expect('Gabrielesbaiz\NovaFieldIndicator')
    ->toUseStrictTypes();

arch('concerns are traits')
    ->expect('Gabrielesbaiz\NovaFieldIndicator\Concerns')
    ->toBeTraits();

arch('contracts are interfaces')
    ->expect('Gabrielesbaiz\NovaFieldIndicator\Contracts')
    ->toBeInterfaces();

arch('enums are string backed')
    ->expect('Gabrielesbaiz\NovaFieldIndicator\Enums')
    ->toBeStringBackedEnums();

arch('support classes are final')
    ->expect('Gabrielesbaiz\NovaFieldIndicator\Support')
    ->toBeClasses()
    ->toBeFinal();

arch('results are immutable value objects')
    ->expect('Gabrielesbaiz\NovaFieldIndicator\Results')
    ->toBeFinal()
    ->toBeReadonly()
    ->toImplement(Arrayable::class);

arch('the field stays open for subclassing')
    ->expect(NovaFieldIndicator::class)
    ->not->toBeFinal();

/*
 * The load-bearing rule. Keeping resolution free of Nova is what lets the
 * colour, enum and threshold logic be unit-tested without booting an app, and
 * it is why the suite runs in well under a second.
 */
arch('resolution stays framework free')
    ->expect([
        'Gabrielesbaiz\NovaFieldIndicator\Support',
        'Gabrielesbaiz\NovaFieldIndicator\Results',
        'Gabrielesbaiz\NovaFieldIndicator\Enums',
        'Gabrielesbaiz\NovaFieldIndicator\Contracts',
    ])
    ->not->toUse('Laravel\Nova');

arch('the package does not depend on its own tests')
    ->expect('Gabrielesbaiz\NovaFieldIndicator')
    ->not->toUse('Gabrielesbaiz\NovaFieldIndicator\Tests');
