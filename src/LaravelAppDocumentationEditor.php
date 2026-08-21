<?php

namespace Artisansplatform\LaravelAppDocumentationEditor;

use Illuminate\Support\HtmlString;

class LaravelAppDocumentationEditor
{
    /**
     * Get CSS for the document editor
     */
    public static function css(): HtmlString
    {
        $url = route('laravel-app-documentation-editor.assets.css');

        return new HtmlString('<link rel="stylesheet" href="'.$url.'">');
    }

    /**
     * Get JavaScript for the document editor
     */
    public static function js(): HtmlString
    {
        $url = route('laravel-app-documentation-editor.assets.js');

        return new HtmlString('<script src="'.$url.'"></script>');
    }
}
