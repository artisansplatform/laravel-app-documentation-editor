# Document Editor for Laravel

A powerful, Vite-powered document editor package for Laravel applications that enables seamless in-app document editing with GitHub integration. Perfect for documentation sites, wikis, and content management systems.

## 🔍 Overview

Document Editor is a Laravel package that provides a modern, inline document editing experience directly within your application. It features a powerful markdown editor with WYSIWYG capabilities, GitHub integration for version control, and customizable authorization methods to control who can edit documents.

## ✨ Features

- **Modern Markdown Editor** - Integrated Toast UI Editor with WYSIWYG capabilities
- **GitHub Integration** - Create pull requests directly from the editor interface
- **Flexible Authorization** - Customizable methods to control who can edit documents
- **Responsive Design** - Modern Bootstrap 5 styling that works on all devices
- **Path Configuration** - Include specific document paths by default it will take the root path.
- **Version Control** - Compare changes with visual diffs before saving
- **Event System** - Listen for editor events in your JavaScript

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 11.x
- Composer
- Node.js and npm (for building assets)

## 📦 Installation

### Via Composer

```bash
composer require misusonu18/document-editor
```

### Publish Configuration

```bash
# Publish the configuration file
php artisan vendor:publish --provider="Misusonu18\DocumentEditor\Providers\DocumentEditorServiceProvider" --tag="document-editor-config"
```

## ⚙️ Configuration

After publishing the configuration file, you can customize the package settings in `config/document-editor.php`:

### Document Paths

Configure which document paths to include in the editor:

```php
'include_document_path' => [
    'docs',
    'resources/docs'
],
```

### GitHub Integration

Set up GitHub integration for creating pull requests:

```php
'github' => [
    'enabled' => env('DOCUMENT_MANAGER_GITHUB_ENABLED', false),
    'token' => env('DOCUMENT_MANAGER_GITHUB_TOKEN'),
    'owner' => env('DOCUMENT_MANAGER_GITHUB_OWNER'),
    'repository' => env('DOCUMENT_MANAGER_GITHUB_REPOSITORY'),
    'base_branch' => env('DOCUMENT_MANAGER_GITHUB_BASE_BRANCH', 'main'),
],
```

### Authorization

Control who can edit documents:

```php
'auth' => [
    'enabled' => env('DOCUMENT_MANAGER_AUTH_ENABLED', true),
    'method' => env('DOCUMENT_MANAGER_AUTH_METHOD', 'params'), // 'callback' or 'params'
    'params_key' => env('DOCUMENT_MANAGER_AUTH_PARAMS_KEY', 'edit-by-pm'),
    'params_value' => env('DOCUMENT_MANAGER_AUTH_PARAMS_VALUE', true),
    'callback' => [\App\Services\DocumentAuth::class, 'checkPermission'], // Your callback class and method
],
```

#### Authorization Methods

This package provides two authorization methods to control document editing permissions:

1. **URL Parameters Method**: Simple access control using URL parameters
2. **Callback Method**: Advanced access control using custom logic

##### Example 1: URL Parameters Method

Configure the URL parameters method:

```php
// In config/document-editor.php
'auth' => [
    'enabled' => true,
    'method' => 'params',
    'params_key' => 'edit-access',
    'params_value' => 'true',
],
```

Then access the editor by adding the parameter to your URL:

```
https://your-app.test/document-editor?folderName=abc&filePath=documentation.md&edit-access=true
```

##### Example 2: Callback Method

Configure the callback method, and that method should be static:

```php
// In config/document-editor.php
'auth' => [
    'enabled' => true,
    'method' => 'callback',
    'callback' => [\App\Services\DocumentAuth::class, 'checkPermission'],
],
```

Create your authorization class:

```php
// File: app/Services/DocumentAuth.php
namespace App\Services;

class DocumentAuth
{
    public function checkPermission()
    {
        // Your Logic Goes Here.
    }
}
```

### Document Structure

The editor supports browsing and editing files in your Laravel project, with configurable paths and authorization methods. Files in the `vendor` and `node_modules` directories are automatically excluded.

## 🔄 GitHub Integration

When GitHub integration is enabled, the package provides:

1. **Document Browser** - Navigate through project files
2. **Markdown Editor** - Edit documents with real-time preview
3. **Pull Request Creation** - Submit changes directly from the editor
4. **Visual Diffs** - Compare changes with the original before submitting

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## ✨ Credits

- [misusonu18](https://github.com/misusonu18)

---

Made with ❤️ for the Laravel community
