<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

use Flarum\Foundation\KnownError;
use RuntimeException;
use Throwable;

/**
 * A connection's provider didn't list its models: it's unreachable, refused
 * the key, or answered with something that isn't a list. API clients get a
 * 502 with the code `ai_models_unavailable`.
 */
class ModelsUnavailableException extends RuntimeException implements KnownError
{
    public function __construct(
        public readonly string $connection,
        ?Throwable $previous = null
    ) {
        parent::__construct("The models of the AI connection \"$connection\" could not be listed.", 0, $previous);
    }

    public function getType(): string
    {
        return 'ai_models_unavailable';
    }
}
