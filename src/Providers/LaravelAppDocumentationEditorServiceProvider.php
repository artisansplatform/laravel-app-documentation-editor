<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Providers;

use Artisansplatform\LaravelAppDocumentationEditor\Enums\MethodTypes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelAppDocumentationEditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $allowedModes = MethodTypes::values();
        $envMode = config('laravel-app-documentation-editor.auth.method', 'PARAMS');

        if (!in_array($envMode, $allowedModes, true)) {
            throw new \InvalidArgumentException(sprintf("Invalid LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_METHOD: '%s'. Allowed values are: ", $envMode) . implode(', ', $allowedModes));
        }

        $this->mergeConfigFrom(__DIR__.'/../config/laravel-app-documentation-editor.php', 'laravel-app-documentation-editor');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-app-documentation-editor'];
    }

    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/laravel-app-documentation-editor.php' => $this->app->configPath('laravel-app-documentation-editor.php'),
        ], 'laravel-app-documentation-editor-config');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'laravel-app-documentation-editor');

        // Register routes
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        // Only register routes if enabled in config
        if (config('laravel-app-documentation-editor.route.enabled', true)) {
            Route::group($this->routeConfiguration(), function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../routes/laravel_app_documentation_editor.php');
            });
        }
    }

    /**
     * Get route group configuration array.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('laravel-app-documentation-editor.url_name'),
            'middleware' => ['web'],
            'as' => 'laravel-app-documentation-editor.',
        ];
    }
}
