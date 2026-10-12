<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('laravel-app-documentation-editor::partials.head', ['pageTitle' => 'Editing '.(request()->string('folderName')->value() ?: basename($filePath ?? '')).' · Documentation'])
</head>
<body class="h-full overflow-hidden bg-white font-sans text-slate-700 antialiased dark:bg-slate-950 dark:text-slate-300">
    @php
        $documentName = request()->string('folderName')->value() ?: basename($filePath ?? '');
    @endphp

    <div class="flex h-full flex-col">
        <header class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-4 sm:px-6 dark:border-slate-800">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('laravel-app-documentation-editor.index') }}"
               class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
               aria-label="Back to documentation" title="Back to documentation">
                <i data-lucide="arrow-left" class="size-[18px]"></i>
            </a>

            <div class="min-w-0">
                <p class="text-xs text-slate-500 dark:text-slate-400">Editing</p>
                <h1 class="truncate text-sm font-semibold text-slate-900 dark:text-white" title="{{ $filePath }}">{{ $documentName }}</h1>
            </div>

            <span data-change-status data-dirty="false"
                  class="hidden shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 sm:inline-flex data-[dirty=true]:bg-amber-50 data-[dirty=true]:text-amber-700 dark:bg-slate-800 dark:text-slate-400 dark:data-[dirty=true]:bg-amber-500/10 dark:data-[dirty=true]:text-amber-400">
                <span class="size-1.5 rounded-full bg-current"></span>
                <span data-change-status-label>No changes</span>
            </span>

            @if($hasSubmitApproval && $baseSha)
                <span class="hidden shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-600 lg:inline-flex dark:bg-slate-800 dark:text-slate-400"
                      title="You are editing the latest version on {{ $baseBranch }}. The pull request will target this branch.">
                    <i data-lucide="git-branch" class="size-3.5"></i>
                    {{ $baseBranch }}
                </span>
            @elseif($hasSubmitApproval)
                <span class="hidden shrink-0 items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 lg:inline-flex dark:bg-amber-500/10 dark:text-amber-400"
                      title="This file could not be loaded from the {{ $baseBranch }} branch, so you are editing the local copy. The pull request may include other differences between your local copy and {{ $baseBranch }}.">
                    <i data-lucide="triangle-alert" class="size-3.5"></i>
                    Local copy, not {{ $baseBranch }}
                </span>
            @endif

            <div class="ml-auto flex items-center gap-2">
                @include('laravel-app-documentation-editor::partials.theme-toggle')

                <span title="{{ ! $hasSubmitApproval ? 'You need to configure GitHub credentials to submit changes.' : 'Open a pull request with your changes.' }}">
                    <button id="saveChangesButton" type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-slate-950"
                            @if(! $hasSubmitApproval) disabled @endif>
                        <i data-lucide="git-pull-request" class="size-4"></i>
                        <span class="hidden sm:inline">Submit changes</span>
                        <span class="sm:hidden">Submit</span>
                    </button>
                </span>
            </div>
        </header>

        <div class="flex shrink-0 gap-1 border-b border-slate-200 px-4 py-2 md:hidden dark:border-slate-800" role="tablist">
            <button type="button" role="tab" data-pane-tab="editor" aria-selected="true"
                    class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 aria-selected:bg-slate-100 aria-selected:text-slate-900 dark:text-slate-400 dark:aria-selected:bg-slate-800 dark:aria-selected:text-white">
                Editor
            </button>
            <button type="button" role="tab" data-pane-tab="changes" aria-selected="false"
                    class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 aria-selected:bg-slate-100 aria-selected:text-slate-900 dark:text-slate-400 dark:aria-selected:bg-slate-800 dark:aria-selected:text-white">
                Changes
            </button>
        </div>

        <main data-editor-layout class="grid min-h-0 flex-1 md:grid-cols-2 md:divide-x md:divide-slate-200 dark:md:divide-slate-800">
            <section data-pane="editor" class="editor-pane flex min-h-0 flex-col md:flex">
                <div class="relative min-h-0 flex-1">
                    <div id="editor" class="absolute inset-0"></div>
                </div>
            </section>

            <section data-pane="changes" class="hidden min-h-0 flex-col bg-slate-50 md:flex dark:bg-slate-900/40">
                <div class="hidden h-[46px] shrink-0 items-center gap-3 border-b border-slate-200 px-4 text-xs font-medium uppercase tracking-wider text-slate-500 md:flex dark:border-slate-800 dark:text-slate-400">
                    Changes
                    <span data-diff-stats class="ml-auto font-mono normal-case tracking-normal"></span>
                </div>
                <div class="min-h-0 flex-1 overflow-auto">
                    <div id="diffPreview"></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }

        function initializePage() {
            window.DocumentEditor.init({
                content: {!! json_encode($content) !!},
                filePath: "{{ $filePath ?? '' }}",
                baseSha: @json($baseSha),
                folderName: "{{ request()->string('folderName')->value() ?? '' }}",
                indexUrl: "{{ route('laravel-app-documentation-editor.index') }}",
            });

            document.getElementById('saveChangesButton').addEventListener('click', function () {
                window.DocumentEditor.saveChanges("{{ route('laravel-app-documentation-editor.update') }}");
            });

            window.addEventListener('pagehide', window.DocumentEditor.destroy);
        }
    </script>
</body>
</html>
