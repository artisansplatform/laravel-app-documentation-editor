<?php

namespace Artisansplatform\LaravelAppDocumentationEditor\Exceptions;

use Exception;

/**
 * Thrown when the document on the base branch changed after the editor loaded it.
 */
class DocumentOutOfDateException extends Exception {}
