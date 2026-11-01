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

        return 'Hide or restore a discussion or post, where its canHide allows: update_resource with {"isHidden": true} or false (delete_resource deletes it for good). '
            .'Mark read: update_resource on discussions with {"lastReadPostNumber": n}, or on your user with {"markedAllAsReadAt": "<ISO 8601 date>"}.';
    }
}
