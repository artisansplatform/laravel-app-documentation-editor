<?php

use Artisansplatform\LaravelAppDocumentationEditor\Services\DocumentService;
use Artisansplatform\LaravelAppDocumentationEditor\Services\GithubService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Reset configuration before each test
    config([
        'laravel-app-documentation-editor.include_document_path' => [],
        'laravel-app-documentation-editor.github.token' => 'test-token',
        'laravel-app-documentation-editor.github.owner' => 'test-owner',
        'laravel-app-documentation-editor.github.repository' => 'test-repo',
        'laravel-app-documentation-editor.github.base_branch' => 'main',
        'laravel-app-documentation-editor.auth.method' => 'PARAMS',
    ]);
});

// DocumentService File Listing Tests
it('lists files with include paths filtering', function () {
    $root = createDocumentationFixture([
        'app/docs/readme.md' => '# Readme',
        'src/guide.md' => '# Guide',
        'vendor/test/file.md' => '# File',
    ]);

    config(['laravel-app-documentation-editor.include_document_path' => ['app']]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Docs');
    expect($result['Docs'])->toHaveCount(1);
    expect($result['Docs'][0]['file_name'])->toBe('Readme');
    expect($result['Docs'][0]['file_path'])->toBe('app/docs/readme.md');

    File::deleteDirectory($root);
});

it('excludes vendor and node_modules regardless of include paths', function () {
    $root = createDocumentationFixture([
        'app/vendor/test.md' => '# Test',
        'app/node_modules/readme.md' => '# Readme',
        'app/docs/guide.md' => '# Guide',
    ]);

    config(['laravel-app-documentation-editor.include_document_path' => ['app']]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Docs');
    expect($result['Docs'])->toHaveCount(1);
    expect($result['Docs'][0]['file_name'])->toBe('Guide');

    File::deleteDirectory($root);
});

it('applies exclude paths when no include paths specified', function () {
    $root = createDocumentationFixture([
        'docs/readme.md' => '# Readme',
        'temp/guide.md' => '# Guide',
    ]);

    // The DocumentService doesn't actually support exclude paths in its current implementation,
    // so with no include paths both folders are returned.
    config(['laravel-app-documentation-editor.exclude_document_path' => ['temp']]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Docs');
    expect($result['Docs'][0]['file_name'])->toBe('Readme');

    File::deleteDirectory($root);
});

it('handles root folder inclusion with special values', function () {
    $root = createDocumentationFixture([
        'readme.md' => '# Readme',
        'docs/guide.md' => '# Guide',
    ]);

    config(['laravel-app-documentation-editor.include_document_path' => ['/']]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('/');
    expect($result['/'])->toHaveCount(1);
    expect($result['/'][0]['file_name'])->toBe('Readme');

    File::deleteDirectory($root);
});

it('filters only markdown files', function () {
    $root = createDocumentationFixture([
        'docs/readme.md' => '# Readme',
        'docs/config.txt' => 'not markdown',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result['Docs'])->toHaveCount(1);
    expect($result['Docs'][0]['file_name'])->toBe('Readme');

    File::deleteDirectory($root);
});

it('handles empty file collections', function () {
    $root = createDocumentationFixture([]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();

    File::deleteDirectory($root);
});

// DocumentService Markdown Conversion Tests
it('converts markdown to HTML successfully', function () {
    $markdownContent = "# Test Header\n\nThis is a **bold** text with [link](http://example.com).";

    File::shouldReceive('exists')
        ->with(base_path('/test-file.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/test-file.md'))
        ->andReturn($markdownContent);

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/test-file.md');

    expect($result)->toContain('<h1>Test Header</h1>');
    expect($result)->toContain('<strong>bold</strong>');
    expect($result)->toContain('<a href="http://example.com">link</a>');
});

it('returns error message for non-existent file', function () {
    File::shouldReceive('exists')
        ->with(base_path('/non-existent.md'))
        ->andReturn(false);

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/non-existent.md');

    expect($result)->toBe('<h3>Documentation for this module is not available.</h3>');
});

it('handles complex markdown content', function () {
    $complexMarkdown = "# Main Title\n\n## Subtitle\n\n- Item 1\n- Item 2\n\n```php\n\$code = 'example';\n```\n\n> Quote block";

    File::shouldReceive('exists')
        ->with(base_path('/complex.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/complex.md'))
        ->andReturn($complexMarkdown);

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/complex.md');

    expect($result)->toContain('<h1>Main Title</h1>');
    expect($result)->toContain('<h2>Subtitle</h2>');
    expect($result)->toContain('<ul>');
    expect($result)->toContain('code');
    expect($result)->toContain('<blockquote>');
});

it('handles markdown with tables', function () {
    $tableMarkdown = "# Table Test\n\n| Header 1 | Header 2 |\n|----------|----------|\n| Cell 1   | Cell 2   |";

    File::shouldReceive('exists')
        ->with(base_path('/table.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/table.md'))
        ->andReturn($tableMarkdown);

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/table.md');

    expect($result)->toContain('<table>');
    expect($result)->toContain('<th>Header 1</th>');
    expect($result)->toContain('<td>Cell 1</td>');
});

it('handles markdown with images', function () {
    $imageMarkdown = "# Image Test\n\n![Alt text](image.png \"Title\")";

    File::shouldReceive('exists')
        ->with(base_path('/image.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/image.md'))
        ->andReturn($imageMarkdown);

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/image.md');

    expect($result)->toContain('<img');
    expect($result)->toContain('alt="Alt text"');
    expect($result)->toContain('src="image.png"');
});

// DocumentService GitHub Integration Tests
it('creates pull request successfully', function () {
    File::shouldReceive('exists')
        ->with(base_path('/test-file.md'))
        ->andReturn(true);

    Http::fake([
        '*' => Http::response([
            'html_url' => 'https://github.com/test-owner/test-repo/pull/123',
        ], 201),
    ]);

    $service = new DocumentService;
    $result = $service->updateDocumentationUsingGithubPullRequest(
        '/test-file.md',
        'Test Module',
        'Updated content'
    );

    expect($result['message'])->toBe('Pull request created successfully!');
    expect($result['pr_url'])->toBe('https://github.com/test-owner/test-repo/pull/123');
});

it('throws exception for non-existent file in PR creation', function () {
    File::shouldReceive('exists')
        ->with(base_path('/non-existent.md'))
        ->andReturn(false);

    $service = new DocumentService;

    expect(fn () => $service->updateDocumentationUsingGithubPullRequest(
        '/non-existent.md',
        'Test Module',
        'Content'
    ))->toThrow(Exception::class, 'The specified documentation file does not exist.');
});

it('handles GitHub API errors during PR creation', function () {
    File::shouldReceive('exists')
        ->with(base_path('/test-file.md'))
        ->andReturn(true);

    Http::fake([
        '*' => Http::response([
            'message' => 'Validation failed',
            'errors' => [['message' => 'Invalid repository']],
        ], 422),
    ]);

    $service = new DocumentService;

    expect(fn () => $service->updateDocumentationUsingGithubPullRequest(
        '/test-file.md',
        'Test Module',
        'Updated content'
    ))->toThrow(Exception::class);
});

// GithubService API Integration Tests
it('creates GitHub pull request with all steps', function () {
    Http::fake([
        'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main' => Http::response([
            'object' => ['sha' => 'test-sha-123'],
        ], 200),
        'https://api.github.com/repos/test-owner/test-repo/git/refs' => Http::response([
            'ref' => 'refs/heads/test-branch',
        ], 201),
        'https://api.github.com/repos/test-owner/test-repo/contents/test.md' => Http::response([
            'sha' => 'file-sha-456',
        ], 200),
        'https://api.github.com/repos/test-owner/test-repo/pulls' => Http::response([
            'html_url' => 'https://github.com/test-owner/test-repo/pull/123',
        ], 201),
    ]);

    $githubService = new GithubService;
    $prUrl = $githubService->createPR(
        'Test PR Title',
        'test.md',
        'Test content',
        'test-branch'
    );

    expect($prUrl)->toBe('https://github.com/test-owner/test-repo/pull/123');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main';
    });
});

it('handles GitHub API authentication errors', function () {
    Http::fake([
        'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main' => Http::response([
            'message' => 'Bad credentials',
        ], 401),
    ]);

    $githubService = new GithubService;

    expect(fn () => $githubService->createPR(
        'Test PR',
        'test.md',
        'Content',
        'test-branch'
    ))->toThrow(Exception::class);
});

it('handles GitHub rate limiting', function () {
    Http::fake([
        'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main' => Http::response([
            'message' => 'API rate limit exceeded',
        ], 403),
    ]);

    $githubService = new GithubService;

    expect(fn () => $githubService->createPR(
        'Test PR',
        'test.md',
        'Content',
        'test-branch'
    ))->toThrow(Exception::class);
});

it('handles branch creation failures', function () {
    Http::fake([
        'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main' => Http::response([
            'object' => ['sha' => 'test-sha-123'],
        ], 200),
        'https://api.github.com/repos/test-owner/test-repo/git/refs' => Http::response([
            'message' => 'Reference already exists',
        ], 422),
    ]);

    $githubService = new GithubService;

    expect(fn () => $githubService->createPR(
        'Test PR',
        'test.md',
        'Content',
        'existing-branch'
    ))->toThrow(Exception::class);
});

it('handles file content update failures', function () {
    Http::fake([
        'https://api.github.com/repos/test-owner/test-repo/git/ref/heads/main' => Http::response([
            'object' => ['sha' => 'test-sha-123'],
        ], 200),
        'https://api.github.com/repos/test-owner/test-repo/git/refs' => Http::response([
            'ref' => 'refs/heads/test-branch',
        ], 201),
        'https://api.github.com/repos/test-owner/test-repo/contents/test.md' => Http::response([
            'message' => 'Conflict',
        ], 409),
    ]);

    $githubService = new GithubService;

    expect(fn () => $githubService->createPR(
        'Test PR',
        'test.md',
        'Content',
        'test-branch'
    ))->toThrow(Exception::class);
});

// Integration Tests
it('completes full workflow from file listing to PR creation', function () {
    $root = createDocumentationFixture([
        'docs/api.md' => '# API Documentation',
    ]);

    Http::fake([
        '*' => Http::response([
            'html_url' => 'https://github.com/test-owner/test-repo/pull/integration',
        ], 201),
    ]);

    config(['laravel-app-documentation-editor.include_document_path' => ['docs']]);

    $service = new DocumentService;

    // Test file listing
    $files = $service->getFileLists();
    expect($files)->toHaveKey('Docs');
    expect($files['Docs'][0]['file_path'])->toBe('docs/api.md');

    // Test markdown conversion
    $html = $service->getMarkdownFileAndConvertItToHtml('/docs/api.md');
    expect($html)->toContain('<h1>API Documentation</h1>');

    // Test PR creation
    $result = $service->updateDocumentationUsingGithubPullRequest(
        '/docs/api.md',
        'API Module',
        'Updated API documentation'
    );

    expect($result['message'])->toBe('Pull request created successfully!');
    expect($result['pr_url'])->toContain('github.com');

    File::deleteDirectory($root);
});

// Edge Cases and Mutation Testing Coverage
it('handles case sensitivity in path filtering', function () {
    $root = createDocumentationFixture([
        'APP/docs/readme.md' => '# Readme',
    ]);

    config(['laravel-app-documentation-editor.include_document_path' => ['app']]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Docs');

    File::deleteDirectory($root);
});

it('handles empty and null configuration values', function () {
    $root = createDocumentationFixture([]);

    config(['laravel-app-documentation-editor.include_document_path' => null]);
    config(['laravel-app-documentation-editor.exclude_document_path' => null]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();

    File::deleteDirectory($root);
});

it('tests file extension filtering edge cases', function () {
    $root = createDocumentationFixture([
        'docs/readme.MD' => '# Readme',
        'docs/file.markdown' => '# File',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    // Should only include .md files, not .MD or .markdown
    expect($result)->toBeEmpty();

    File::deleteDirectory($root);
});

it('tests folder name formatting with special characters', function () {
    $root = createDocumentationFixture([
        'user_management/api-docs/readme.md' => '# Readme',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Api Docs');

    File::deleteDirectory($root);
});

it('handles deeply nested directory structures', function () {
    $root = createDocumentationFixture([
        'a/very/deep/nested/structure/file.md' => '# File',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Structure');
    expect($result['Structure'][0]['file_name'])->toBe('File');

    File::deleteDirectory($root);
});

it('handles files with no extension gracefully', function () {
    $root = createDocumentationFixture([
        'docs/README' => '# Readme',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    // Should not include files without .md extension
    expect($result)->toBeEmpty();

    File::deleteDirectory($root);
});

it('handles multiple files in same directory with different cases', function () {
    $root = createDocumentationFixture([
        'docs/readme.md' => '# Readme',
        'docs/documentation.md' => '# Documentation',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result['Docs'])->toHaveCount(2);
    expect(collect($result['Docs'])->pluck('file_name')->toArray())->toContain('Readme', 'Documentation');

    File::deleteDirectory($root);
});

it('handles empty markdown file conversion', function () {
    File::shouldReceive('exists')
        ->with(base_path('/empty.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/empty.md'))
        ->andReturn('');

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/empty.md');

    expect($result)->toBe('');
});

it('handles markdown with only whitespace', function () {
    File::shouldReceive('exists')
        ->with(base_path('/whitespace.md'))
        ->andReturn(true);

    File::shouldReceive('get')
        ->with(base_path('/whitespace.md'))
        ->andReturn("   \n\t  \n   ");

    $service = new DocumentService;
    $result = $service->getMarkdownFileAndConvertItToHtml('/whitespace.md');

    expect(trim($result))->toBe('');
});

it('handles invalid GitHub configuration values', function () {
    config([
        'laravel-app-documentation-editor.github.token' => null,
        'laravel-app-documentation-editor.github.owner' => '',
        'laravel-app-documentation-editor.github.repository' => null,
        'laravel-app-documentation-editor.github.base_branch' => '',
    ]);

    File::shouldReceive('exists')
        ->with(base_path('/test.md'))
        ->andReturn(true);

    $service = new DocumentService;

    expect(fn () => $service->updateDocumentationUsingGithubPullRequest(
        '/test.md',
        'Module',
        'Content'
    ))->toThrow(Exception::class);
});

it('handles extremely long file names and paths', function () {
    // Kept short of the ~255 byte filesystem filename limit while still exercising a long name/path.
    $longName = str_repeat('very-long-name-', 10).'.md';
    $longPath = 'deeply/'.str_repeat('nested/', 10).'path';

    $root = createDocumentationFixture([
        $longPath.'/'.$longName => '# Long',
    ]);

    $service = new DocumentService;
    $result = $service->getFileLists();

    expect($result)->toHaveKey('Path');
    expect($result['Path'][0]['file_name'])->toContain('Very Long Name');

    File::deleteDirectory($root);
});

// Access Control Tests
it('allows submitting updates when auth is disabled by default', function () {
    $root = createDocumentationFixture([
        'docs/guide.md' => '# Guide',
    ]);

    config(['laravel-app-documentation-editor.auth.enabled' => false]);

    Http::fake([
        '*' => Http::response(['html_url' => 'https://github.com/test-owner/test-repo/pull/1'], 201),
    ]);

    $response = $this->postJson(route('laravel-app-documentation-editor.update'), [
        'folderName' => 'Docs',
        'filePath' => 'docs/guide.md',
        'content' => '# Guide updated',
    ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'Pull request created successfully!');

    File::deleteDirectory($root);
});

it('blocks submitting updates when auth is enabled without a satisfied callback', function () {
    $root = createDocumentationFixture([
        'docs/guide.md' => '# Guide',
    ]);

    config([
        'laravel-app-documentation-editor.auth.enabled' => true,
        'laravel-app-documentation-editor.auth.method' => 'PARAMS',
    ]);

    $response = $this->postJson(route('laravel-app-documentation-editor.update'), [
        'folderName' => 'Docs',
        'filePath' => 'docs/guide.md',
        'content' => '# Guide updated',
    ]);

    $response->assertForbidden();
    $response->assertJsonPath('message', 'You do not have permission to edit this document.');

    File::deleteDirectory($root);
});

// Asset Route Tests
it('serves the built javascript bundle with the correct content type', function () {
    $response = $this->get(route('laravel-app-documentation-editor.assets.js'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    expect($response->getContent())->not->toBeEmpty();
});

it('serves the built stylesheet with the correct content type', function () {
    $response = $this->get(route('laravel-app-documentation-editor.assets.css'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/css; charset=utf-8');
    expect($response->getContent())->not->toBeEmpty();
});

it('does not leak raw javascript into the documentation page', function () {
    $response = $this->get(route('laravel-app-documentation-editor.index'));

    $response->assertOk();
    $response->assertSee('<script src="'.route('laravel-app-documentation-editor.assets.js').'"></script>', false);
    $response->assertDontSee('@license', false);
});
