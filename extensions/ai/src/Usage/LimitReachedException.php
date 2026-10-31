<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Flarum\Foundation\KnownError;
use RuntimeException;

/**
 * An AI call was stopped before it was made, because it would go over a
 * token limit the admin set.
 */
class LimitReachedException extends RuntimeException implements KnownError
{
    /**
     * @param 'connection'|'feature'|'member' $limit
     */
    public function __construct(
        public readonly string $limit,
        public readonly string $name,
        public readonly int $tokens
    ) {
        parent::__construct(match ($limit) {
            'connection' => "The AI connection \"$name\" has used its $tokens tokens for this month.",
            'feature' => "The AI feature \"$name\" has used its $tokens tokens for today.",
            'member' => "This member has used their $tokens AI tokens for today.",
        });
    }

    public function getType(): string
    {
        return 'ai_limit_reached';
    }
}
