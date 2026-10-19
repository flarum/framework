<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Something the admin asked for that cannot be done, and why.
 *
 * The message is already translated and written for the person reading it, so
 * RefusedExceptionHandler, registered through Extend\ErrorHandling, passes it
 * to the browser as the error's detail along with the type and status given
 * here. Any other exception reaches the browser as Flarum's generic error,
 * never by its message.
 *
 * Not a KnownError: Flarum answers those before it asks a handler, without
 * the detail.
 */
class RefusedException extends RuntimeException
{
    public function __construct(
        string $message,
        protected string $type = 'refused',
        protected int $status = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
