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

    /**
     * Parse Blade template for testing without using the built-in method
     */
    protected function parseBladeString(string $string): string
    {
        return app('blade.compiler')->compileString($string);
    }
}
