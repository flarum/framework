<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Throwable;

/**
 * An error that was caught / interpreted by Flarum's error handling stack.
 *
 * Most importantly, such an error has a "type" (which is used to look up
 * translated error messages and views to render pretty HTML pages) and an
 * associated HTTP status code for used in rendering HTTP error responses.
 */
class HandledError
{
    private array $details = [];

    public static function unknown(Throwable $error): static
    {
        return new static($error, 'unknown', 500);
    }

    public function __construct(
        private readonly Throwable $error,
        private readonly string $type,
        private readonly int $statusCode,
        private bool $report = false
    ) {
    }

    public function withDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getException(): Throwable
    {
        return $this->error;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function shouldBeReported(): bool
    {
        return $this->type === 'unknown' || $this->report;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function hasDetails(): bool
    {
        return ! empty($this->details);
    }
}
