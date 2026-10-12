<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle ?? 'Documentation' }}</title>

<script>
    // Apply the theme before first paint to avoid a light/dark flash.
    (function () {
        var storedTheme = null;
        try {
            storedTheme = localStorage.getItem('laravel-app-documentation-editor-theme');
        } catch (error) {}
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (storedTheme === 'dark' || (storedTheme === null && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

{{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::css() }}
{{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::js() }}
