<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Services;

use Artisansplatform\LaravelAppDocumentationEditor\Exceptions\DocumentOutOfDateException;
use Artisansplatform\LaravelAppDocumentationEditor\Exceptions\GithubRequestException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class GithubService
{
    protected ?string $token;

    protected ?string $repo;

    protected ?string $owner;

    protected string $apiBase = 'https://api.github.com/repos/';

    protected ?string $baseBranch = null;

    public function __construct()
    {
        $this->token = config('laravel-app-documentation-editor.github.token');
        $this->repo = config('laravel-app-documentation-editor.github.repository');
        $this->owner = config('laravel-app-documentation-editor.github.owner');
        $this->baseBranch = config('laravel-app-documentation-editor.github.base_branch');
    }

    public function isEnvConfigured(): bool {
        return !is_null($this->token) && !is_null($this->repo) && !is_null($this->owner);
    }

    public function getBaseBranch(): ?string
    {
        return $this->baseBranch;
    }

    /**
     * Get a file's content and blob SHA as it currently is on the base branch
     *
     * @param  bool  $failFast  Use a short timeout and no retries, for callers that have a fallback
     * @return array{content: string, sha: string}|null Null when the file is not on the base branch or cannot be read
     */
    public function getFileFromBaseBranch(string $filePath, bool $failFast = false): ?array
    {
        try {
            $baseBranchFile = $this->readBaseBranchFile($filePath, $failFast);
        } catch (Exception) {
            return null;
        }

        // Files over 1 MB come back without inline content
        if ($baseBranchFile === null || $baseBranchFile['content'] === null) {
            return null;
        }

        return $baseBranchFile;
    }

    /**
     * Read a file from the base branch
     *
     * @return array{content: string|null, sha: string}|null Null when the file is not on the base branch
     *
     * @throws Exception When GitHub cannot be reached or answers with an error other than 404
     */
    protected function readBaseBranchFile(string $filePath, bool $failFast = false): ?array
    {
        try {
            $fileData = $this->request(
                'GET',
                'contents/'.ltrim($filePath, '/'),
                ['ref' => $this->baseBranch],
                retryTimes: $failFast ? 1 : 3,
                timeoutSeconds: $failFast ? 5 : null,
            );
        } catch (GithubRequestException $githubRequestException) {
            if ($githubRequestException->status === 404) {
                return null;
            }

            throw $githubRequestException;
        }

        if (! isset($fileData['sha'])) {
            return null;
        }

        $hasInlineContent = ($fileData['encoding'] ?? null) === 'base64' && isset($fileData['content']);

        return [
            'content' => $hasInlineContent ? base64_decode(str_replace("\n", '', $fileData['content'])) : null,
            'sha' => $fileData['sha'],
        ];
    }

    /**
     * Create a pull request with the provided changes
     *
     * @param  string|null  $expectedBaseSha  Blob SHA the edit was based on; when it no longer matches the base branch the PR is refused
     */
    public function createPR(string $title, string $filePath, string $content, string $branchName = 'update-docs', ?string $expectedBaseSha = null): string
    {
        $filePath = ltrim($filePath, '/');

        // 1. Refuse to overwrite changes made on the base branch since the editor was opened
        // A failed read throws instead of being mistaken for a changed document
        if ($expectedBaseSha !== null) {
            $currentBaseSha = $this->readBaseBranchFile($filePath)['sha'] ?? null;

            if ($currentBaseSha !== $expectedBaseSha) {
                throw new DocumentOutOfDateException(sprintf(
                    'This document was changed on the %s branch after you opened it. Reload the editor and apply your edits again.',
                    $this->baseBranch
                ));
            }
        }

        // 2. Create the branch from the latest base branch commit
        $this->createBranch($branchName);

        // 3. Update file in the branch
        $this->updateFile($filePath, $content, $branchName);

        // 4. Create the pull request
        $prUrl = $this->createPullRequest($title, $branchName);

        return $prUrl;
    }

    /**
     * Create a new branch
     */
    protected function createBranch(string $newBranch): void
    {
        // Get the latest commit SHA from the base branch
        try {
            $commitSha = $this->request('GET', 'git/ref/heads/'.$this->baseBranch)['object']['sha'] ?? null;
        } catch (Exception) {
            $commitSha = null;
        }

        if ($commitSha === null) {
            throw new Exception(sprintf(
                'Base branch "%s" could not be found in %s/%s. Check LARAVEL_APP_DOCUMENTATION_EDITOR_GITHUB_BASE_BRANCH.',
                $this->baseBranch,
                $this->owner,
                $this->repo
            ));
        }

        // Create a new branch using that commit
        try {
            $this->request('POST', 'git/refs', [
                'ref' => 'refs/heads/'.$newBranch,
                'sha' => $commitSha,
            ]);
        } catch (GithubRequestException $githubRequestException) {
            // An existing branch is reused; any other failure is real
            $branchAlreadyExists = $githubRequestException->status === 422
                && str_contains($githubRequestException->body, 'Reference already exists');

            if (! $branchAlreadyExists) {
                throw $githubRequestException;
            }
        }
    }

    /**
     * Update a file in a branch
     */
    protected function updateFile(string $filePath, string $content, string $branch): void
    {
        // Check if file exists
        try {
            $fileData = $this->request('GET', 'contents/'.$filePath, ['ref' => $branch]);
            $sha = $fileData['sha'];
        } catch (Exception) {
            // File doesn't exist
            $sha = null;
        }

        // Create or update file
        $this->request('PUT', 'contents/'.$filePath, [
            'message' => 'Update '.$filePath,
            'content' => base64_encode($content),
            'branch' => $branch,
            'sha' => $sha,
        ]);
    }

    /**
     * Create a pull request
     */
    protected function createPullRequest(string $title, string $head): string
    {
        $response = $this->request('POST', 'pulls', [
            'title' => $title,
            'head' => $head,
            'base' => $this->baseBranch,
        ]);

        return $response['html_url'];
    }

    /**
     * Make a request to the GitHub API
     */
    protected function request(string $method, string $endpoint, array $data = [], int $retryTimes = 3, ?int $timeoutSeconds = null): array
    {
        $url = $this->apiBase.sprintf('%s/%s/', $this->owner, $this->repo).ltrim($endpoint, '/');

        // Only retry failures that can succeed on a second try
        $isRetryable = fn (Exception $exception): bool => $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());

        $response = Http::withToken($this->token)
            ->retry($retryTimes, 100, $isRetryable, throw: false)
            ->when($timeoutSeconds !== null, fn ($pendingRequest) => $pendingRequest->timeout($timeoutSeconds))
            ->{strtolower($method)}($url, $data);

        if ($response->failed()) {
            $this->logger()->error('GitHub API failed: '.$method.' '.$endpoint, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new GithubRequestException($response->status(), $response->body());
        }

        // Response bodies can hold whole documents, so only the status is logged
        $this->logger()->debug('GitHub API success: '.$method.' '.$endpoint, [
            'status' => $response->status(),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Log to the "github" channel when the application defines one, otherwise to the default channel
     */
    protected function logger(): LoggerInterface
    {
        return config('logging.channels.github') !== null ? Log::channel('github') : Log::channel();
    }
}
