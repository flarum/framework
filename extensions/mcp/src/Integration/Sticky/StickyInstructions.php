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

        return 'Sticky discussions stay at the top of lists. To sticky or unsticky one, if you may, call update_resource on discussions with {"isSticky": true} or false.';
    }
}
