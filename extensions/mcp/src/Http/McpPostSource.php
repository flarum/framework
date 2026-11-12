<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\OAuthProvider\Models\Client;
use Flarum\Post\PostSource;

/**
 * The "via …" a post made through MCP shows.
 *
 * The authorization code always passes through the browser of the member who
 * approves the connection, and that member is the author of what it posts. A
 * public client needs no secret to redeem the code, so a member can obtain a
 * token for any public client, whatever its name, host or redirect URIs say.
 * Only a confidential client proves who it is, and only an admin creates one,
 * so only its name is shown. Everything else is "via MCP".
 */
class McpPostSource
{
    public const string KEY = 'mcp';
    public const string LABEL = 'MCP';

    public static function forClient(?Client $client): PostSource
    {
        $named = $client !== null && $client->source === Client::SOURCE_ADMIN && $client->confidential;

        return new PostSource(self::KEY, $named ? $client->name : self::LABEL);
    }
}
