<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Documentation Edit</title>

    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::css() }}
    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::js() }}

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="overflow-hidden font-[Inter] bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 min-h-screen">
    <div class="h-screen flex flex-col">
        <header class="flex items-center justify-between p-4 md:p-6 bg-white/80 backdrop-blur-xl border-b border-slate-200/50 shadow-sm">
            <div class="flex items-center gap-4">
                <a href="{{ route('laravel-app-documentation-editor.index') }}"
                   class="flex items-center gap-2 px-4 py-2 text-slate-600 bg-slate-100/80 hover:bg-slate-200/80 rounded-lg transition-all duration-200">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back</span>
                </a>
                <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">Document Editor</h1>
            </div>
        </header>

        <main class="flex-1 grid grid-rows-2 md:grid-rows-1 md:grid-cols-2 gap-4 p-4 md:p-6 overflow-y-auto md:overflow-hidden">
            <!-- Markdown Editor -->
            <div class="flex flex-col bg-white/85 backdrop-blur-xl rounded-xl border border-slate-200/50 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-slate-200/50 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="edit-3" class="w-5 h-5 text-slate-500"></i>
                        <h2 class="font-semibold text-slate-700">Editor</h2>
                    </div>
                    <button id="previewButton"
                            class="md:hidden flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors duration-200">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span>Preview</span>
                    </button>
                </div>
                <div class="flex-1 overflow-auto min-h-[300px]">
                    <div id="editor" class="w-full h-full p-4"></div>
                </div>
            </div>

            <!-- Preview -->
            <div id="previewPanel" class="flex flex-col bg-white/85 backdrop-blur-xl rounded-xl border border-slate-200/50 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-slate-200/50 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="eye" class="w-5 h-5 text-slate-500"></i>
                        <h2 class="font-semibold text-slate-700">Preview</h2>
                    </div>
                    <button id="editorButton"
                            class="md:hidden flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors duration-200"
">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Editor</span>
                    </button>
                </div>
                <div class="flex-1 overflow-auto min-h-[300px]">
                    <article id="diffPreview" class="prose max-w-none p-4"></article>
                </div>
            </div>
        </main>

        <footer class="sticky bottom-0 p-4 md:p-6 bg-white/80 backdrop-blur-xl border-t border-slate-200/50">
            <div class="flex justify-end">
                <span title="{{ !$hasSubmitApproval ? 'You need to configure GitHub credentials to submit changes.' : 'Proceed with submitting changes.' }}">
                    <button id="saveChangesButton"
                            class="flex items-center gap-2 px-6 py-2.5 text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md"
                            @if(!$hasSubmitApproval) disabled @endif>
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Submit Changes</span>
                    </button>
                </span>
            </div>
        </footer>
    </div>

    <script>
        // Initialize page when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }

        function initializePage() {
            // Initialize Lucide icons
            if (typeof window.createIcons === 'function') {
                window.createIcons();
            }

            // Initialize the DocumentEditor from editor.js
            window.DocumentEditor.init({
                content: {!! json_encode($content) !!},
                filePath: "{{ $filePath ?? '' }}",
                folderName: "{{ request()->string('folderName')->value() ?? '' }}",
                indexUrl: "{{ route('laravel-app-documentation-editor.index') }}",
            });

            // Set up mobile view buttons
            document.getElementById('previewButton')?.addEventListener('click', () => {
                document.getElementById('previewPanel')?.scrollIntoView({ behavior: 'smooth' });
            });

            document.getElementById('editorButton')?.addEventListener('click', () => {
                document.getElementById('editor')?.parentElement?.parentElement?.scrollIntoView({ behavior: 'smooth' });
            });

            // Set up save button
            document.getElementById('saveChangesButton').addEventListener('click', function() {
                window.DocumentEditor.saveChanges("{{ route('laravel-app-documentation-editor.update') }}");
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', window.DocumentEditor.destroy);
        }
    </script>
</body>
</html>
