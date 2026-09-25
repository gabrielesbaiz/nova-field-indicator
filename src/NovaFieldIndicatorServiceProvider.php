<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator;

use Gabrielesbaiz\NovaFieldIndicator\Console\UpgradeCommand;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;

class NovaFieldIndicatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-field-indicator.php', 'nova-field-indicator');
    }

    public function boot(): void
    {
        $this->registerAssets();
        $this->registerTranslations();
        $this->registerPublishing();
        $this->registerCommands();
    }

    protected function registerAssets(): void
    {
        Nova::serving(function (): void {
            // Handles are namespaced to the package. 2.x registered the very
            // generic `indicator`, which any other package could collide with.
            Nova::script('nova-field-indicator', __DIR__.'/../dist/js/field.js');
            Nova::style('nova-field-indicator', __DIR__.'/../dist/css/field.css');
        });
    }

    protected function registerTranslations(): void
    {
        $lang = __DIR__.'/../lang';

        // Two registrations, deliberately, because this package translates on
        // both sides of the wire. Nova::translations() feeds Nova's JavaScript
        // translator, while labels here are resolved in PHP — boolean(),
        // unknown() and the aria-label template all call __() server-side — and
        // those need the framework translator to know about the same files.
        $this->loadJsonTranslationsFrom($lang);

        Nova::serving(function () use ($lang): void {
            $locale = app()->getLocale();
            $translations = sprintf('%s/%s.json', $lang, $locale);

            Nova::translations(is_file($translations) ? $translations : $lang.'/en.json');
        });
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/nova-field-indicator.php' => config_path('nova-field-indicator.php'),
        ], 'nova-field-indicator-config');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/nova-field-indicator'),
        ], 'nova-field-indicator-lang');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([UpgradeCommand::class]);
        }
    }
}
