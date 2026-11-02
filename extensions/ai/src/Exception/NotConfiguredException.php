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

/**
 * There's no connection to make the call through. API clients get a 503 with
 * the code `ai_not_configured`.
 */
class NotConfiguredException extends RuntimeException implements KnownError, UnavailableException
{
    public function __construct(
        /** The feature that was to make the call, if it went through one. */
        public readonly ?string $feature = null
    ) {
        parent::__construct($feature === null
            ? 'No AI connection is configured. Add one in the admin panel, or under `ai` in config.php.'
            : "The AI feature \"$feature\" has no connection to use. Add one in the AI settings.");
    }

    public function getType(): string
    {
        return 'ai_not_configured';
    }
}
