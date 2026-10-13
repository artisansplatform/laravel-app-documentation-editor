<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('laravel-app-documentation-editor::partials.head', ['pageTitle' => ($title ?? 'Documentation').' · Documentation'])
</head>
<body class="h-full overflow-hidden bg-white font-sans text-slate-700 antialiased dark:bg-slate-950 dark:text-slate-300">
    @php
        $activeFilePath = request()->string('filePath')->value();
    @endphp

    <div class="flex h-full">
        <div data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

        <aside data-sidebar
               class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-slate-50 transition-transform duration-200 ease-out lg:static lg:translate-x-0 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
                <a href="{{ route('laravel-app-documentation-editor.index') }}" class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm">
                        <i data-lucide="book-open" class="size-4"></i>
                    </span>
                    <span class="text-[15px] font-semibold tracking-tight text-slate-900 dark:text-white">Documentation</span>
                </a>
                <button type="button" data-sidebar-close
                        class="ml-auto inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200/70 hover:text-slate-900 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                        aria-label="Close navigation">
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>

            <div class="shrink-0 px-4 pt-4">
                <label class="relative block">
                    <span class="sr-only">Filter documents</span>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" data-nav-filter placeholder="Filter documents"
                           class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-9 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500">
                    <kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-slate-200 px-1.5 font-sans text-[11px] text-slate-400 sm:block dark:border-slate-700">/</kbd>
                </label>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4" aria-label="Documents">
                <ul role="list" class="space-y-0.5">
                    @forelse($directories as $folder => $files)
                        @if(count($files) === 1)
                            @php($isActive = $activeFilePath === $files[0]['file_path'])
                            <li data-nav-entry>
                                <a href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $folder, 'filePath' => $files[0]['file_path']]) }}"
                                   @if($isActive) aria-current="page" @endif
                                   @class([
                                       'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                       'bg-blue-50 font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $isActive,
                                       'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => ! $isActive,
                                   ])>
                                    <i data-lucide="file-text" class="size-4 shrink-0 opacity-70"></i>
                                    <span class="truncate">{{ $folder }}</span>
                                </a>
                            </li>
                        @else
                            @php($hasActiveChild = collect($files)->contains(fn (array $file): bool => $file['file_path'] === $activeFilePath))
                            <li data-nav-entry>
                                <details class="group [&_summary::-webkit-details-marker]:hidden" @if($hasActiveChild) open data-active-group @endif>
                                    <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                                        <i data-lucide="folder" class="size-4 shrink-0 opacity-70"></i>
                                        <span class="truncate">{{ $folder }}</span>
                                        <i data-lucide="chevron-right" class="ml-auto size-4 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-90"></i>
                                    </summary>

                                    <ul role="list" class="my-1 ml-5 space-y-0.5 border-l border-slate-200 pl-2 dark:border-slate-800">
                                        @foreach($files as $file)
                                            @php($isActive = $activeFilePath === $file['file_path'])
                                            <li data-nav-child>
                                                <a href="{{ route('laravel-app-documentation-editor.index', ['folderName' => $file['file_name'], 'filePath' => $file['file_path']]) }}"
                                                   @if($isActive) aria-current="page" @endif
                                                   @class([
                                                       'block truncate rounded-lg px-3 py-1.5 text-sm transition-colors',
                                                       'bg-blue-50 font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' => $isActive,
                                                       'text-slate-500 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => ! $isActive,
                                                   ])>
                                                    {{ $file['file_name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            </li>
                        @endif
                    @empty
                        <li class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400">No documentation found.</li>
                    @endforelse
                </ul>

                <p data-nav-empty class="hidden px-3 py-2 text-sm text-slate-500 dark:text-slate-400">No matching documents.</p>
            </nav>

            <div class="flex shrink-0 items-center justify-between border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                <span class="text-xs text-slate-500 dark:text-slate-400">Appearance</span>
                @include('laravel-app-documentation-editor::partials.theme-toggle')
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8 dark:border-slate-800 dark:bg-slate-950/90">
                <button type="button" data-sidebar-open
                        class="-ml-1 inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                        aria-label="Open navigation">
                    <i data-lucide="menu" class="size-5"></i>
                </button>

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $title ?? 'Documentation' }}</p>
                    @if($activeFilePath)
                        <p class="truncate font-mono text-xs text-slate-500 dark:text-slate-400">{{ $activeFilePath }}</p>
                    @endif
                </div>

                @if ($hasEditAccess && request()->has('filePath') && request()->has('folderName'))
                    <a href="{{ route('laravel-app-documentation-editor.edit') }}?filePath={{ request()->string('filePath') }}&folderName={{ request()->string('folderName') }}"
                       class="ml-auto inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950">
                        <i data-lucide="pencil" class="size-4"></i>
                        <span class="hidden sm:inline">Edit document</span>
                    </a>
                @endif
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto">
                @isset($content)
                    <article class="document-content prose prose-slate mx-auto max-w-3xl px-4 py-10 sm:px-8 lg:py-14 dark:prose-invert">
                        {!! $content !!}
                    </article>
                @else
                    <div class="mx-auto flex max-w-md flex-col items-center px-6 py-24 text-center">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                            <i data-lucide="book-open" class="size-6"></i>
                        </span>
                        <h1 class="mt-5 text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $title ?? 'Documentation' }}</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Select a document from the sidebar to start reading.</p>
                        <button type="button" data-sidebar-open
                                class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 lg:hidden dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i data-lucide="menu" class="size-4"></i>
                            Browse documents
                        </button>
                    </div>
                @endisset
            </main>
        </div>
    </div>
</body>
</html>
