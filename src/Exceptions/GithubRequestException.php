<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Exceptions;

use Exception;

/**
 * Thrown when the GitHub API answers with an error status.
 */
class GithubRequestException extends Exception
{
    public function __construct(public readonly int $status, public readonly string $body)
    {
        parent::__construct('GitHub API error: '.$body, $status);
    }
}
