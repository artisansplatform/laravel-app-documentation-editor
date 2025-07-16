<?php

namespace Misusonu18\DocumentEditor\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Misusonu18\DocumentEditor\DocumentEditor;
use Misusonu18\DocumentEditor\Services\DocumentService;

class DocumentEditorController extends Controller
{
    public function __construct(protected DocumentService $documentService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $menuList = Cache::remember('document_editor_menu_list', 60, function () {
            return $this->documentService->getFileLists();
        });

        if (config('document-editor.auth.method') === 'params' && $request->has(config('document-editor.auth.params_key')) && $request->boolean(config('document-editor.auth.params_key')) === config('document-editor.auth.params_value')) {
            return $this->edit($request);
        }

        if ($request->has('folderName') && $request->has('filePath')) {
            $folderName = $request->string('folderName');
            $filePath = $request->string('filePath');

            return view('document-editor::index', [
                'directories' => $menuList,
                'title' => $folderName,
                'content' => $this->getFileContent($filePath),
                'hasEditAccess' => $this->haveTheEditAccess(),
            ]);
        }

        return view('document-editor::index', [
            'directories' => $menuList,
            'title' => 'Welcome To Document Manager',
            'hasEditAccess' => false,
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $filePath = $request->string('filePath')->value();

        if (empty($filePath) || !File::exists(base_path($filePath))) {
            return back()->with('error', 'File not found or does not exist.');
        }

        return view('document-editor::manage', [
            'content' => $this->documentService->getFile($filePath),
            'filePath' => $filePath,
        ]);
    }

    public function update(Request $request): array|RedirectResponse
    {
        // Check if user has edit access
        if (!$this->haveTheEditAccess()) {
            return back()->with('error', 'You do not have permission to edit this document.');
        }

        // Validate inputs
        $request->validate([
            'folderName' => 'required|string',
            'filePath' => 'required|string',
            'content' => 'required|string',
        ]);

        $domainName = $request->string('folderName');
        $filePath = $request->string('filePath');
        $content = $request->string('content');

        // Check if file exists
        if (!File::exists(base_path($filePath))) {
            return back()->with('error', 'File not found or does not exist.');
        }

        return $this->documentService->updateDocumentationUsingGithubPullRequest(
            $filePath,
            $domainName,
            $content
        );
    }

    private function getFileContent(string $filePath): string
    {
        if (empty($filePath)) {
            return 'File path is required';
        }

        // Ensure file exists
        if (!File::exists(base_path($filePath))) {
            return 'File does not exist';
        }

        return $this->documentService->getMarkdownFileAndConvertItToHtml($filePath);
    }

    private function haveTheEditAccess(): bool
    {
        if (! config('document-editor.auth.enabled')) {
            return true;
        }

        if (config('document-editor.auth.use_custom_callback')) {
            return app()->call(config('document-editor.auth.callback'));
        }

        return false;
    }
}
