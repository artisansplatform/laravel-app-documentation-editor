# Authorization Methods

The Laravel App Documentation Editor provides flexible authorization options to control who can edit your documentation.

## Available Methods

### 1. URL Parameters Method

This simple approach enables editing based on URL parameters.

#### Configuration

In your `.env` file:
```
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_ENABLED=true
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_METHOD=params
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_KEY=edit-access
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_PARAMS_VALUE=true
```

Or in `config/laravel-app-documentation-editor.php`:
```php
'auth' => [
    'enabled' => true,
    'method' => 'params',
    'params_key' => 'edit-access',
    'params_value' => 'true',
],
```

#### Usage

Access the editor with the parameter in your URL:
```
https://your-app.com/laravel-app-documentation-editor/documentation?edit-access=true
```

### 2. Callback Method

For more advanced authorization logic, use a custom callback.

#### Configuration

In your `.env` file:
```
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_ENABLED=true
LARAVEL_APP_DOCUMENTATION_EDITOR_AUTH_METHOD=callback
```

In `config/laravel-app-documentation-editor.php`:
```php
'auth' => [
    'enabled' => true,
    'method' => 'callback',
    'callback' => [\App\Services\DocumentAuth::class, 'checkPermission'],
],
```

#### Implementation

Create your authorization class with a static method:

```php
// File: app/Services/DocumentAuth.php
namespace App\Services;

class DocumentAuth
{
    public static function checkPermission()
    {
        // Your custom authorization logic here
        // For example:
        return auth()->user() && auth()->user()->hasPermission('edit-documentation');
    }
}
```
