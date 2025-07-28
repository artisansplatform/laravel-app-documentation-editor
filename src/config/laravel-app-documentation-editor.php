<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Editor Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the document editor package.
    |
    */

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
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configure GitHub integration for creating pull requests.
    |
    */
    'github' => [
        'enabled' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_ENABLED', false),
        'token' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_TOKEN'),
        'owner' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_OWNER'),
        'repository' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_REPOSITORY'),
        'base_branch' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_BASE_BRANCH', 'main'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Simple authorization configuration for document management.
    |
    */
    'auth' => [
        // Enable or disable authentication
        'enabled' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_ENABLED', false),

        'method' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_METHOD', ''), // 'callback' or 'params'

        'params_key' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_KEY', ''),
        'params_value' => env('LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_VALUE', false),
    ],
];
