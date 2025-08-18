# Laravel App Documentation Editor

Tired of switching between tools to update your docs? Our editor brings documentation management right into your Laravel app. Edit markdown files, preview changes instantly, and submit GitHub PRs—all in one sleek interface.

## ✨ Features

- **📝 Modern Markdown Editor** - Rich editing with real-time preview
- **🔄 GitHub Integration** - Create pull requests without leaving your app
- **🔒 Flexible Authorization** - Control who can edit your documentation
- **📱 Responsive Design** - Works beautifully on all devices
- **🗂️ Document Browser** - Navigate your project files with ease
- **👁️ Visual Diffs** - Compare changes before submitting
- **🧩 Event System** - Hook into editor events from your JavaScript

## 📦 Installation

```bash
composer require artisansplatform/laravel-app-documentation-editor
```

## 🔗 Access URL

```
https://your-app.com/laravel-app-documentation-editor/documentation
```

> **Note:** The URL path can be customized in the configuration file using the `url_name` setting.

## ⚙️ Configuration (Optional)

Publish the configuration file to customize paths, GitHub integration, and access control:

```bash
php artisan vendor:publish --provider="Artisansplatform\LaravelAppDocumentationEditor\Providers\LaravelAppDocumentationEditorServiceProvider" --tag="laravel-app-documentation-editor-config"
```

This creates `config/laravel-app-documentation-editor.php` where you can set:
- Document paths
- GitHub repository details
- Authorization methods
- Custom URL name

## 🔒 Authorization

Control who can edit your documentation using flexible authorization methods:

- URL Parameters Method - Simple access control via URL parameters
- Callback Method - Advanced control with custom logic

[See detailed authorization documentation](AUTHORIZATION.md)

## 🔄 GitHub Integration

Enable GitHub integration to submit document changes as pull requests:

```php
// In your .env file
LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_TOKEN=your_token
LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_OWNER=your_username
LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_REPOSITORY=your_repo
LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_BASE_BRANCH=main
```

For detailed instructions on creating a GitHub token, see the [GitHub Token Creation Guide](github_token_creation.md).

## 📋 Requirements

- PHP 8.2+
- Laravel 11.x
- Composer

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## ✨ Credits

- [Mishal Parmar](https://github.com/misusonu18)

---

Made with ❤️ by [Artisans Platform](https://github.com/artisansplatform)
