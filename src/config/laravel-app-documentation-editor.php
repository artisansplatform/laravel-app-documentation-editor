<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Include Document Path
    |--------------------------------------------------------------------------
    |
    | Define the path where folder documents paths are included. If not specified, then root folder will be used.
    |
    */
    'include_document_path' => [],

    /*
    |--------------------------------------------------------------------------
    | Update The Url Name
    |--------------------------------------------------------------------------
    |
    | Define the URL name to be used for the documentation editor. If not specified, a default URL name will be used.
    |
    */
    'url_name' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_URL_NAME', 'laravel-app-documentation-editor'),

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configure GitHub integration for creating pull requests.
    | For token creation, please check the [Github Token Creation Docs](https://github.com/artisansplatform/laravel-app-documentation-editor/blob/main/github_token_creation.md)
    |
    */
    'github' => [
        'token' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_TOKEN'),
        'owner' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_OWNER'),
        'repository' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_REPOSITORY'),
        'base_branch' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_BASE_BRANCH', 'main'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Editing Settings
    |--------------------------------------------------------------------------
    |
    | Simple editing configuration for document editor.
    |
    */
    'auth' => [
        // Enable or disable editing
        'enabled' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_ENABLED', false),

        'method' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_METHOD', ''), // 'callback' or 'params'

        'params_key' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_KEY', ''),
        'params_value' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_VALUE', false),
    ],
];
