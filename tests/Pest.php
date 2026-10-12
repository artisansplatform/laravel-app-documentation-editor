<?php

use Artisansplatform\LaravelAppDocumentationEditor\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Write real files to a fresh temp directory, point the application's base
 * path at it, and return the directory so the caller can clean it up.
 *
 * @param  array<string, string>  $files  Relative path => file contents.
 */
function createDocumentationFixture(array $files): string
{
    $root = sys_get_temp_dir().'/doc-editor-test-'.Str::random(8);

    File::ensureDirectoryExists($root);

    foreach ($files as $relativePath => $content) {
        $fullPath = $root.DIRECTORY_SEPARATOR.ltrim($relativePath, '/');
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $content);
    }

    app()->setBasePath($root);

    return $root;
}

/**
 * Fake every GitHub API call made while opening a pull request against the
 * "main" base branch of test-owner/test-repo.
 *
 * @param  string  $baseFileContent  Content of the edited file on the base branch.
 * @param  string  $baseFileSha  Blob SHA of the edited file on the base branch.
 */
function fakeGithubPullRequestFlow(
    string $pullRequestUrl = 'https://github.com/test-owner/test-repo/pull/1',
    string $baseFileContent = '# Base',
    string $baseFileSha = 'base-file-sha',
): void {
    $repositoryUrl = 'https://api.github.com/repos/test-owner/test-repo';

    Http::fake([
        $repositoryUrl.'/git/ref/heads/main' => Http::response(['object' => ['sha' => 'base-commit-sha']], 200),
        $repositoryUrl.'/git/refs' => Http::response(['ref' => 'refs/heads/update'], 201),
        $repositoryUrl.'/contents/*' => Http::response([
            'sha' => $baseFileSha,
            'encoding' => 'base64',
            'content' => chunk_split(base64_encode($baseFileContent), 60, "\n"),
        ], 200),
        $repositoryUrl.'/pulls' => Http::response(['html_url' => $pullRequestUrl], 201),
    ]);
}
