<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Throwable;

class HandledError
{
    private $error;
    private $type;
    private $statusCode;

    private $details = [];

    public static function unknown(Throwable $error)
    {
        return new static($error, 'unknown', 500);
    }

    public function __construct(Throwable $error, $type, $statusCode)
    {
        $this->error = $error;
        $this->type = $type;
        $this->statusCode = $statusCode;
    }

    public function withDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getError(): Throwable
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
        return $this->type === 'unknown';
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
