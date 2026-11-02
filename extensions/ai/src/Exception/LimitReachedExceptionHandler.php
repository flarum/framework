<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

use Carbon\Carbon;
use Flarum\Foundation\ErrorHandling\HandledError;

/**
 * Tells API clients which limit was reached and when it resets. Flarum's
 * error formatter can't set headers, so this is in place of a Retry-After.
 *
 * @internal
 */
class LimitReachedExceptionHandler
{
    public function handle(LimitReachedException $e): HandledError
    {
        return (new HandledError($e, $e->getType(), 429))->withDetails([[
            'meta' => [
                'limit' => $e->limit->value,
                'resetsAt' => $e->resetsAt->toIso8601String(),
                'retryAfter' => (int) max(0, Carbon::now()->diffInSeconds($e->resetsAt, false)),
            ],
        ]]);
    }
}
