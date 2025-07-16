<?php

namespace Mishal\DocumentEditor\Tests;

use Mishal\DocumentEditor\DocumentEditorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DocumentEditorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('document-editor.theme', 'light');
        $app['config']->set('document-editor.height', 500);
    }
}
