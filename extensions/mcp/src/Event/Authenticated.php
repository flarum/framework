<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Event;

use Flarum\Mcp\Http\McpAuthentication;

/**
 * An MCP request was admitted, as its bearer or as a guest. The forum's
 * middleware ran for a guest before this, so per-request actor state it sets
 * is set here.
 */
class Authenticated
{
    public function __construct(
        public readonly McpAuthentication $authentication,
    ) {
    }
}
