<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Tests;

use Artisansplatform\LaravelAppDocumentationEditor\Providers\LaravelAppDocumentationEditorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAppDocumentationEditorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /**
     * Parse Blade template for testing without using the built-in method
     */
    protected function parseBladeString(string $string): string
    {
        return app('blade.compiler')->compileString($string);
    }
}
