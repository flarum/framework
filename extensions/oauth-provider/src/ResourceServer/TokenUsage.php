<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ResourceServer;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\AccessToken;

/**
 * Records when, and from where, an access token was last presented: to a
 * resource server such as MCP, or to /oauth/userinfo. Members see it under
 * Connected applications, and admins on each client.
 */
class TokenUsage
{
    /**
     * last_used_at is written at most once per this many seconds per token.
     */
    public const int INTERVAL = 60;

    /**
     * @param string|null $ipAddress Where the request came from. Without one,
     *                               the last known address stays.
     */
    public function record(AccessToken $token, ?string $ipAddress = null): void
    {
        $now = Carbon::now();

        if ($token->last_used_at !== null && $token->last_used_at->diffInSeconds($now, true) < self::INTERVAL) {
            return;
        }

        AccessToken::query()->where('id', $token->id)->update(array_filter([
            'last_used_at' => $now,
            'last_ip_address' => $ipAddress,
        ], fn ($value) => $value !== null));
    }
}
