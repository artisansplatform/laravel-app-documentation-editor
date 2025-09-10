<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Manager - Dynamic Documentation</title>

    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::css() }}
    {{ Artisansplatform\LaravelAppDocumentationEditor\LaravelAppDocumentationEditor::js() }}

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="overflow-hidden font-[Inter] bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100">
    <script>
        // Initialize after app.js is loaded
        function initializePage() {
            if (typeof window.createIcons === 'function') {
                window.createIcons();
                const menuBtn = document.getElementById('menuBtn');
                const sidebar = document.getElementById('sidebar');
                menuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('fixed');
                    sidebar.classList.toggle('inset-0');
                    sidebar.classList.toggle('z-50');
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }
    </script>
    <div class="relative grid grid-cols-1 md:grid-cols-12 h-screen transition-all duration-500 ease-in-out">
        <button id="menuBtn" class="md:hidden fixed top-4 right-4 z-50 p-2 bg-white/80 backdrop-blur-xl rounded-lg shadow-md border border-slate-200/50 transition-all duration-300 hover:bg-slate-50">
            <i data-lucide="menu" class="w-6 h-6 text-slate-600"></i>
        </button>
        <aside id="sidebar" class="hidden md:block md:col-span-3 border-r border-slate-200/60 bg-slate-50/90 backdrop-blur-xl shadow-[0_0_40px_-15px_rgba(0,0,0,0.15)] h-screen transition-all duration-500 ease-in-out transform hover:shadow-lg overflow-y-auto">
            <div class="flex flex-col h-full border-e border-slate-200/50 bg-gradient-to-b from-slate-50/90 to-white/80">
            <div class="flex flex-col h-full border-e border-gray-100/50 bg-gradient-to-b from-white/80 to-gray-50/80 overflow-hidden">
                <div class="px-4 py-2 flex flex-col h-full">
                    <div class="flex-shrink-0">
                        <div class="flex items-center px-6 py-6 border-b border-gray-200/50 bg-gradient-to-r from-white via-gray-50/80 to-blue-50/30">
                            <div class="flex items-center gap-4 group">
                                <div class="p-2.5 bg-gradient-to-br from-blue-500/15 to-blue-600/20 rounded-xl shadow-sm transition-all duration-300 ease-in-out group-hover:shadow-md group-hover:from-blue-500/20 group-hover:to-blue-600/25">
                                    <i data-lucide="book-open" class="w-6 h-6 text-blue-600/90 transition-colors duration-300 ease-in-out group-hover:text-blue-700"></i>
                                </div>
                                <span class="text-2xl font-semibold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent tracking-tight">Document Manager</span>
                            </div>
                        </div>
                    </div>

                    <ul role="list" class="px-3 space-y-1.5 overflow-y-auto flex-1 pb-4">
                        @forelse($directories as $folder => $files)
                            @if(count($files) === 1)
                                <!-- Single file folder - direct link -->
                                <li>
                                    <a href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $folder, 'filePath' => $files[0]['file_path']]) }}"
                                        class="group flex items-center gap-x-3 rounded-md p-2 mx-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all duration-200 ease-in-out transform hover:translate-x-1">
                                        <i data-lucide="file-text" class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-200"></i>
                                        {{ $folder }}
                                    </a>
                                </li>
                            @else
                                <!-- Multi-file folder - collapsible -->
                                <li>
                                    <details class="group [&_summary::-webkit-details-marker]:hidden">
                                        <summary
                                            class="flex cursor-pointer items-center justify-between rounded-lg px-4 py-2 hover:bg-gray-100 hover:text-gray-700 mx-2 transition-all duration-200 ease-in-out group hover:shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <i data-lucide="folder" class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-200"></i>
                                                <span class="text-sm font-medium">{{ $folder }}</span>
                                            </div>

                                            <span class="shrink-0 transition duration-300 group-open:-rotate-180">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </summary>

                                        <ul class="mt-2 space-y-1 px-4">
                                            @foreach($files as $file)
                                                <li>
                                                    <a href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $file['file_name'], 'filePath' => $file['file_path']]) }}"
                                                        class="flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-all duration-200 ease-in-out transform hover:translate-x-1 group">
                                                        <i data-lucide="file-text" class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-200"></i>
                                                        {{ $file['file_name'] }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </li>
                            @endif
                        @empty
                            <li>
                                <a href="#"
                                    class="block rounded-lg px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                    No documentation found.
                                </a>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </aside>

        <main class="col-span-1 md:col-span-9 p-4 md:p-8 bg-gradient-to-br from-slate-100/95 via-slate-50/90 to-blue-50/20 h-screen overflow-hidden">
            <div class="h-full bg-slate-200 backdrop-blur-xl border border-slate-200/40 rounded-2xl shadow-[0_0_50px_-20px_rgba(0,0,0,0.12)] p-4 md:p-8 overflow-y-auto transition-all duration-500 ease-in-out transform hover:shadow-lg">
                <div class="max-w-4xl mx-auto">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-blue-600/10 rounded-xl">
                                <i data-lucide="file" class="w-6 h-6 text-blue-600"></i>
                            </div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                                {{ $title ?? 'Dashboard Content' }}
                            </h1>
                        </div>

                        @if ($hasEditAccess && request()->has('filePath') && request()->has('folderName'))
                            <a href="{{ route('laravel-app-documentation-editor.edit') }}?filePath={{ request()->string('filePath') }}&folderName={{ request()->string('folderName') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                Edit Document
                            </a>
                        @endif
                    </div>

                    <div class="mt-6 document-content prose max-w-none bg-white/60 rounded-xl p-4 sm:p-6 md:p-8 shadow-sm border border-slate-200/50 transition-all duration-500 ease-in-out transform hover:shadow-md hover:bg-white/75 hover:border-slate-300/50"
                    >
                    {!! $content ?? '<p>Select a file from the sidebar to view its documentation</p>' !!}
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
