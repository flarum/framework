<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Exception;

use RuntimeException;
use Tobyz\JsonApiServer\Exception\ErrorProvider;

/**
 * An error document an `api` middleware answered with instead of throwing.
 */
class ErrorResponse extends RuntimeException implements ErrorProvider
{
    /**
     * @param list<array<string, mixed>> $errors
     */
    public function __construct(
        protected int $status,
        protected array $errors,
    ) {
        parent::__construct("API middleware answered HTTP $status");
    }

    public function getJsonApiErrors(): array
    {
        return $this->errors;
    }

    public function getJsonApiStatus(): string
    {
        return (string) $this->status;
    }
}
