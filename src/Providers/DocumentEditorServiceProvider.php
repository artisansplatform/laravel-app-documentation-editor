<?php

namespace Misusonu18\DocumentEditor\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DocumentEditorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/document-editor.php', 'document-editor');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['document-editor'];
    }

    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/document-editor.php' => $this->app->configPath('document-editor.php'),
        ], 'document-editor-config');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'document-editor');

        // Register routes
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        // Only register routes if enabled in config
        if (config('document-editor.route.enabled', true)) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__ . '/../../routes/document_editor.php');
            });
        }
    }

    /**
     * Get route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'prefix' => 'document-editor',
            'middleware' => ['web'],
            'as' => 'document-editor.',
        ];
    }
}
