<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Sticky;

use Flarum\Mcp\Capability\McpContext;

/**
 * Stickying uses the generic `update_resource` tool: `isSticky` is a writable
 * discussion attribute, gated by flarum/sticky's `sticky` ability.
 */
class StickyInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        $text = 'Sticky discussions stay at the top of lists.';

        // Anywhere: the permission may be granted for some tags only.
        if ($context->actor->hasPermissionLike('discussion.sticky')) {
            $text .= ' Sticky: update_resource on discussions with {"isSticky": true} or false.';
        }

        return $text;
    }
}
