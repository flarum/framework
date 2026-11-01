<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Approval;

use Flarum\Mcp\Capability\McpContext;

/**
 * Approving uses the generic `update_resource` tool: `isApproved` is a
 * writable post attribute, gated by flarum/approval's `approve` ability.
 */
class ApprovalInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if ($context->actor->isGuest()) {
            return null;
        }

        return 'New discussions and posts may await approval (isApproved false) and are then only visible to their author and moderators. '
            .'To approve a post, if you may, call update_resource on posts with {"isApproved": true}; approving a discussion\'s first post approves the discussion.';
    }
}
