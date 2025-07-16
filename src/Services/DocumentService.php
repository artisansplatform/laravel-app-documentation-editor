<?php

namespace Misusonu18\DocumentEditor\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentService
{
    public function getFileLists(): array
    {
        $includeDocumentPaths = config('document-editor.include_document_path');

        $files = collect(File::allFiles(base_path('/')))
            ->reject(function ($file) use ($includeDocumentPaths) {
                $path = Str::lower($file->getPathname());
                $relativePath = $file->getRelativePath();

                // Always exclude vendor and node_modules folders (highest priority)
                if (
                    Str::contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) ||
                    Str::contains($path, DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR)
                ) {
                    return true;
                }

                // If include paths are specified, only include files from those paths
                if (! empty($includeDocumentPaths)) {
                    $isIncluded = collect($includeDocumentPaths)->contains(function ($includePath) use ($path, $relativePath) {
                        // Include root folder if '/' or '' or '.' or 'root' is specified
                        if (in_array($includePath, ['/', '', '.', 'root'])) {
                            return $relativePath === '';
                        }

                        return Str::contains($path, $includePath);
                    });

                    // If include paths are specified but file is not included, reject it
                    if (! $isIncluded) {
                        return true;
                    }
                }

                // File passed all checks, don't reject it
                return false;
            })
            ->filter(function ($file) {
                return Str::endsWith($file->getFilename(), '.md');
            });

        $groupedFolders = $files->groupBy(function ($file) {
            return $this->prepareFormattedTitleForFolderName($file->getRelativePath());
        });

        $menus = [];

        foreach ($groupedFolders as $folderName => $groupedFolderFiles) {
            $menus[$folderName] = $groupedFolderFiles->map(function ($file) {
                return [
                    'file_name' => $this->formattedTitleCaseFormat($file->getFilenameWithoutExtension()),
                    'file_path' => $file->getRelativePathname(),
                ];
            })->toArray();
        }

        return $menus;
    }

    public function getMarkdownFileAndConvertItToHtml(string $filePath): string
    {
        $filePath = base_path('/'.$filePath);

        if (! File::exists($filePath)) {
            return '<h3>Documentation for this module is not available.</h3>';
        }

        $file = File::get($filePath);

        $markdownHtml = Str::markdown($file);

        return $markdownHtml;
    }

    public function getFile(string $filePath): string
    {
        $filePath = base_path('/'.$filePath);

        if (! File::exists($filePath)) {
            return '<h3>Documentation for this module is not available.</h3>';
        }

        return File::get($filePath);
    }

    public function updateDocumentationUsingGithubPullRequest(
        string $filePath,
        string $moduleName,
        string $content,
    ): array {
        if (! File::exists(base_path('/'.$filePath))) {
            throw new Exception('The specified documentation file does not exist.');
        }

        $pullRequestTitle = 'Update the documentation of '.$moduleName;
        $newBranchName = 'update-documentation-'.Str::slug($moduleName).'-'.time();

        $githubService = new GithubService;

        $prUrl = $githubService->createPR(
            $pullRequestTitle,
            $filePath,
            $content,
            $newBranchName
        );

        return [
            'message' => 'Pull request created successfully!',
            'pr_url' => $prUrl,
        ];
    }

    private function formattedTitleCaseFormat(string $name): string
    {
        return Str::title(str_replace(['-', '_'], ' ', $name));
    }

    private function prepareFormattedTitleForFolderName(string $folderName): string
    {
        if ($folderName === '' || $folderName === '') {
            return '/';
        }

        if (Str::contains($folderName, DIRECTORY_SEPARATOR)) {
            $spitedFolderName = explode(DIRECTORY_SEPARATOR, $folderName);

            return Str::of(last($spitedFolderName))
                ->replaceMatches('/(?<!^)([A-Z])/', ' $1') // insert space before uppercase (not at the start)
                ->replace(['-', '_'], ' ') // replace hyphens and underscores with space
                ->title();
        }

        return Str::title(str_replace(['-', '_'], ' ', $folderName));
    }
}
