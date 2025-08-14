<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Http\Controllers;

use Artisansplatform\LaravelAppDocumentationEditor\Enums\MethodTypes;
use Artisansplatform\LaravelAppDocumentationEditor\Services\DocumentService;
use Artisansplatform\LaravelAppDocumentationEditor\Services\GithubService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LaravelAppDocumentationEditorController extends Controller
{
    public function __construct(protected DocumentService $documentService, protected GithubService $githubService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $menuList = Cache::remember('document_editor_menu_list', 60, fn (): array => $this->documentService->getFileLists());

        if (config('laravel-app-documentation-editor.auth.method') === MethodTypes::PARAMS->name && $request->has(config('laravel-app-documentation-editor.auth.params_key')) && $request->boolean(config('laravel-app-documentation-editor.auth.params_key')) === config('laravel-app-documentation-editor.auth.params_value')) {
            return $this->edit($request);
        }

        if ($request->has('folderName') && $request->has('filePath')) {
            $folderName = $request->string('folderName');
            $filePath = $request->string('filePath');

            return view('laravel-app-documentation-editor::index', [
                'directories' => $menuList,
                'title' => $folderName,
                'content' => $this->getFileContent($filePath),
                'hasEditAccess' => $this->haveTheEditAccess(),
            ]);
        }

        return view('laravel-app-documentation-editor::index', [
            'directories' => $menuList,
            'title' => 'Welcome To Document Manager',
            'hasEditAccess' => false,
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $filePath = $request->string('filePath')->value();

        if (empty($filePath) || ! File::exists(base_path($filePath))) {
            return back()->with('error', 'File not found or does not exist.');
        }

        return view('laravel-app-documentation-editor::manage', [
            'content' => $this->documentService->getFile($filePath),
            'filePath' => $filePath,
            'hasSubmitApproval' => $this->githubService->isEnvConfigured()
        ]);
    }

    public function update(Request $request): array|RedirectResponse
    {
        if (! $this->githubService->isEnvConfigured()) {
            return back()->with('error', 'GitHub credentials are not configured.');
        }
        
        // Check if user has edit access
        if (! $this->haveTheEditAccess()) {
            return back()->with('error', 'You do not have permission to edit this document.');
        }

        // Validate inputs
        $request->validate([
            'folderName' => 'required|string',
            'filePath' => 'required|string',
            'content' => 'required|string',
        ]);

        $stringable = $request->string('folderName');
        $filePath = $request->string('filePath');
        $content = $request->string('content');

        // Check if file exists
        if (! File::exists(base_path($filePath))) {
            return back()->with('error', 'File not found or does not exist.');
        }

        return $this->documentService->updateDocumentationUsingGithubPullRequest(
            $filePath,
            $stringable,
            $content
        );
    }

    private function getFileContent(string $filePath): string
    {
        if ($filePath === '' || $filePath === '0') {
            return 'File path is required';
        }

        // Ensure file exists
        if (! File::exists(base_path($filePath))) {
            return 'File does not exist';
        }

        return $this->documentService->getMarkdownFileAndConvertItToHtml($filePath);
    }

    private function haveTheEditAccess(): bool
    {
        if (config('laravel-app-documentation-editor.auth.method') === MethodTypes::CALLBACK->name) {
            return app()->call(config('laravel-app-documentation-editor.auth.callback'));
        }

        return false;
    }
}
