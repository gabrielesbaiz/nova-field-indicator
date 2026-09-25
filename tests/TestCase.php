<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Tests;

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicatorServiceProvider;
use Gabrielesbaiz\NovaFieldIndicator\Support\EnumStates;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // The enum state table is memoized per class for the life of the
        // process, which is what keeps a 50-row index to one cases() pass.
        // Tests need it cleared so one test's fixture cannot leak into another.
        EnumStates::flush();
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            // Nova's core provider resolves Inertia at boot, so Inertia has to
            // be registered before it rather than discovered afterwards.
            InertiaServiceProvider::class,
            NovaCoreServiceProvider::class,
            NovaFieldIndicatorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
    }
}
