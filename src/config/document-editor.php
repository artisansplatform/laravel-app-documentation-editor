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
        'enabled' => env('DOCUMENT_MANAGER_GITHUB_ENABLED', false),
        'token' => env('DOCUMENT_MANAGER_GITHUB_TOKEN'),
        'owner' => env('DOCUMENT_MANAGER_GITHUB_OWNER'),
        'repository' => env('DOCUMENT_MANAGER_GITHUB_REPOSITORY'),
        'base_branch' => env('DOCUMENT_MANAGER_GITHUB_BASE_BRANCH', 'main'),
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
        'enabled' => env('DOCUMENT_MANAGER_AUTH_ENABLED', false),

        'method' => env('DOCUMENT_MANAGER_AUTH_METHOD', ''), // 'callback' or 'params'

        'params_key' => env('DOCUMENT_MANAGER_AUTH_PARAMS_KEY', ''),
        'params_value' => env('DOCUMENT_MANAGER_AUTH_PARAMS_VALUE', false),

        // Use custom callback for authorization logic
        'use_custom_callback' => env('DOCUMENT_MANAGER_USE_CUSTOM_AUTH', false),
    ],
];
