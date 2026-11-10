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
        public readonly ?string $feature = null,
        ?string $message = null
    ) {
        parent::__construct($message ?? ($feature === null
            ? 'No AI connection is configured. Add one in the admin panel, or under `ai` in config.php.'
            : "The AI feature \"$feature\" has no connection to use. Add one in the AI settings."));
    }

    /**
     * A Bedrock connection set up on the admin page with no key, which
     * config.php hasn't let use the server's own AWS credentials.
     */
    public static function bedrockKey(string $connection): self
    {
        return new self(null, "The Bedrock connection \"$connection\" needs a Bedrock API key. Add one in the AI settings, or let it use the server's AWS credentials in config.php.");
    }

    public function getType(): string
    {
        return 'ai_not_configured';
    }
}
