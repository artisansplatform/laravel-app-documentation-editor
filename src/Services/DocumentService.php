<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DocumentService
{
    public  function getFileLists(): array
    {
        $includeDocumentPaths = config('laravel-app-documentation-editor.include_document_path');

        $finder = new Finder();
        $finder->files()
            ->in(base_path())
            ->exclude(['vendor', 'node_modules', 'storage'])
            ->ignoreDotFiles(true)
            ->name('*.md');

        $files = collect($finder)->filter(function ($file) use ($includeDocumentPaths) {
            $path = Str::lower($file->getPathname());
            $relativePath = $file->getRelativePath();

            if (! empty($includeDocumentPaths)) {
                return collect($includeDocumentPaths)->contains(function ($includePath) use ($path, $relativePath) {
                    $includePath = Str::lower(trim($includePath));
                    if (in_array($includePath, ['/', '', '.', 'root'])) {
                        return $relativePath === '';
                    }
                    return Str::contains($path, $includePath);
                });
            }

            return true;
        });

        $groupedFolders = $files->groupBy(fn($file): string => $this->prepareFormattedTitleForFolderName($file->getRelativePath()));

        return $groupedFolders->sortKeys()
            ->map(
                fn($group) =>
                    $group
                        ->map(fn($file) => [
                            'file_name' => $this->formattedTitleCaseFormat($file->getFilenameWithoutExtension()),
                            'file_path' => $file->getRelativePathname(),
                        ])
                        ->sortBy('file_name')
                        ->values()
                        ->toArray()
            )->toArray();
    }

    public function getMarkdownFileAndConvertItToHtml(string $filePath): string
    {
        $filePath = base_path('/'.$filePath);

        if (! File::exists($filePath)) {
            return '<h3>Documentation for this module is not available.</h3>';
        }

        $file = File::get($filePath);

        return Str::markdown($file);
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
