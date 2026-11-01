<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Lock;

use Flarum\Mcp\Capability\McpContext;

/**
 * Locking uses the generic `update_resource` tool: `isLocked` is a writable
 * discussion attribute, gated by flarum/lock's `lock` ability.
 */
class LockInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        $text = 'Locked discussions take no new replies.';

        // Anywhere: the permission may be granted for some tags only.
        if ($context->actor->hasPermissionLike('discussion.lock')) {
            $text .= ' Lock: update_resource on discussions with {"isLocked": true} or false.';
        }

        return $text;
    }
}
