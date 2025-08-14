<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubService
{
    protected ?string $token;

    protected ?string $repo;

    protected ?string $owner;

    protected string $apiBase = 'https://api.github.com/repos/';

    protected ?string $baseBranch = 'https://api.github.com/repos/';

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

    /**
     * Create a pull request with the provided changes
     */
    public function createPR(string $title, string $filePath, string $content, string $branchName = 'update-docs'): string
    {
        // 1. Create branch if it doesn't exist
        try {
            $this->createBranch($branchName);
        } catch (Exception) {
            // Branch might already exist, continue
        }

        // 2. Update file in the branch
        $this->updateFile($filePath, $content, $branchName);

        // 3. Create the pull request
        $prUrl = $this->createPullRequest($title, $branchName);

        return $prUrl;
    }

    /**
     * Create a new branch
     */
    protected function createBranch(string $newBranch): void
    {
        // Get the latest commit SHA from the base branch
        $commitSha = $this->request('GET', 'git/ref/heads/'.$this->baseBranch)['object']['sha'];

        // Create a new branch using that commit
        $this->request('POST', 'git/refs', [
            'ref' => 'refs/heads/'.$newBranch,
            'sha' => $commitSha,
        ]);
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
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiBase.sprintf('%s/%s/', $this->owner, $this->repo).ltrim($endpoint, '/');

        $response = Http::withToken($this->token)
            ->retry(3, 100)
            ->{strtolower($method)}($url, $data);

        if ($response->failed()) {
            Log::channel('github')->error('GitHub API failed: '.$endpoint, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('GitHub API error: '.$response->body());
        }

        Log::channel('github')->error('GitHub API success: '.$endpoint, [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->json();
    }
}
