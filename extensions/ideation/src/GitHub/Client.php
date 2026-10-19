<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

/**
 * Reads one issue, pull request or milestone from GitHub.
 *
 * Returns the item in the shape Links stores, or null when GitHub says it
 * does not exist (or the token cannot see it). Throws Unavailable when GitHub
 * cannot be asked: no answer, rate limited, or a server error.
 */
interface Client
{
    /**
     * @return array{type: string, title: string, state: string, open_issues: ?int, closed_issues: ?int, updated_at: ?string}|null
     */
    public function fetch(Reference $ref): ?array;
}
