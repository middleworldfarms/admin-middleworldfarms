<?php

namespace Middleworld\FarmOSSuccessionPlanner;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SuccessionPlannerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/farmos-succession.php',
            'farmos-succession'
        );

        // Register package services
        $this->app->singleton(Services\FarmOSApiService::class, function ($app) {
            return new Services\FarmOSApiService(
                config('farmos-succession.farmos_url'),
                config('farmos-succession.oauth_client_id'),
                config('farmos-succession.oauth_client_secret')
            );
        });

        $this->app->singleton(Services\SymbiosisAIService::class, function ($app) {
            return new Services\SymbiosisAIService(
                config('farmos-succession.ai_service_url'),
                config('farmos-succession.ai_timeout')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load package views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'succession-planner');

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/farmos-succession.php' => config_path('farmos-succession.php'),
            ], 'config');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/succession-planner'),
            ], 'views');

            // Publish assets
            $this->publishes([
                __DIR__.'/../resources/js' => public_path('vendor/succession-planner/js'),
                __DIR__.'/../resources/css' => public_path('vendor/succession-planner/css'),
            ], 'assets');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }

        // Register Blade directives for package
        $this->registerBladeDirectives();
    }

    /**
     * Register custom Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        \Blade::directive('successionPlannerAssets', function () {
            return '<?php echo view("succession-planner::partials.assets")->render(); ?>';
        });

        \Blade::directive('successionPlannerStyles', function () {
            return '<link href="<?php echo asset("vendor/succession-planner/css/succession-planner.css"); ?>" rel="stylesheet">';
        });

        \Blade::directive('successionPlannerScripts', function () {
            return '<script src="<?php echo asset("vendor/succession-planner/js/succession-timeline.js"); ?>"></script>';
        });
    }
}
