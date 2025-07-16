<?php

namespace Misusonu18\DocumentEditor\Tests;

use Misusonu18\DocumentEditor\Providers\DocumentEditorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DocumentEditorServiceProvider::class,
        ];
    }

    /**
     * Parse Blade template for testing without using the built-in method
     *
     * @param string $string
     * @return string
     */
    protected function parseBladeString(string $string): string
    {
        return app('blade.compiler')->compileString($string);
    }
}
