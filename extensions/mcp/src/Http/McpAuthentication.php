<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\User\User;

/**
 * Who an MCP request acts as, and with which scopes.
 */
class McpAuthentication
{
    public const string GUEST = 'guest';
    public const string DEVELOPER_TOKEN = 'developer_token';
    public const string OAUTH = 'oauth';

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly User $actor,
        public readonly array $scopes,
        public readonly string $method,
    ) {
    }
}
