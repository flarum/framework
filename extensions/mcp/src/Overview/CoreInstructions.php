<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Overview;

use Flarum\Mcp\Capability\McpContext;

/**
 * What core can do through the generic tools without a tool of its own.
 */
class CoreInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        return 'To hide or restore a discussion or post, if you may, call update_resource with {"isHidden": true} or false; delete_resource deletes it for good. '
            .'To mark a discussion read up to a post, call update_resource on discussions with {"lastReadPostNumber": n}; '
            .'to mark everything read, on your own user with {"markedAllAsReadAt": "<an ISO 8601 date>"}.';
    }
}
