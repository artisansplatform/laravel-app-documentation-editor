<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Documentation Edit</title>

    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::css() }}
    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::js() }}
</head>
<body>
    <div class="glass-bg"></div>
    <div class="container-fluid">
        <div class="editor-header">
            <div class="d-flex align-items-center">
                <a href="{{ route('laravel-app-documentation-editor.index') }}" class="btn btn-outline-secondary me-3">
                    Back
                </a>

                <h1>Document Editor</h1>
            </div>
        </div>

        <div class="editor-sections row g-0 mt-5">
            <!-- Markdown Editor -->
            <div class="editor-section">
                <div class="section-title">
                    <span>Editor</span>
                </div>

                <div class="editor-content">
                    <div id="editor" class="w-100 h-100"></div>
                </div>
            </div>

            <!-- Diff Preview -->
            <div class="editor-section">
                <div class="section-title">
                    <span>Preview</span>
                </div>
                <div class="editor-content">
                    <article id="diffPreview" class="prose w-100 h-100"></article>
                </div>
            </div>
        </div>

        <div class="editor-footer" style="padding: 1rem; display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
            <button id="saveChangesButton" class="btn btn-primary">
                Submit Changes
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the DocumentEditor from editor.js
            window.DocumentEditor.init({
                content: {!! json_encode($content) !!},
                filePath: "{{ $filePath ?? '' }}",
                folderName: "{{ request()->string('folderName')->value() ?? '' }}",
                indexUrl: "{{ route('laravel-app-documentation-editor.index') }}",
            });

            // Set up event listeners
            document.getElementById('saveChangesButton').addEventListener('click', function() {
                window.DocumentEditor.saveChanges(' {{ route("laravel-app-documentation-editor.update") }} ');
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', window.DocumentEditor.destroy);
        });
    </script>
</body>
</html>
