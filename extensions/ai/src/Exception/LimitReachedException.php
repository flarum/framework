<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

use Carbon\CarbonImmutable;
use Laravel\Ai\Exceptions\FailoverableException;
use RuntimeException;

/**
 * A call was stopped before it was made, because a token limit the admin set
 * is used up. API clients get a 429 with the code `ai_limit_reached`, and the
 * limit and when it resets in the error's `meta`.
 *
 * laravel/ai treats it as a reason to fail over, so an agent with a fallback
 * provider tries that one next.
 *
 * Not a KnownError: Flarum handles those before any handler, and this one's
 * handler ({@see LimitReachedExceptionHandler}) is what adds the meta.
 */
class LimitReachedException extends RuntimeException implements UnavailableException, FailoverableException
{
    public function __construct(
        public readonly LimitType $limit,
        /** The connection or feature whose limit it is, or the member's username. Null for guests. */
        public readonly ?string $name,
        public readonly int $tokens,
        /** When the limit's period ends, in UTC. */
        public readonly CarbonImmutable $resetsAt
    ) {
        parent::__construct(match ($limit) {
            LimitType::Connection => "The AI connection \"$name\" has used its $tokens tokens for this month.",
            LimitType::Feature => "The AI feature \"$name\" has used its $tokens tokens for today.",
            LimitType::Member => "The member \"$name\" has used their $tokens AI tokens for today.",
            LimitType::Guests => "Guests have used their $tokens AI tokens for today.",
        });
    }

    public function getType(): string
    {
        return 'ai_limit_reached';
    }
}
