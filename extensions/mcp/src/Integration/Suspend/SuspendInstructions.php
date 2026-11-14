<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Suspend;

use Flarum\Mcp\Capability\McpContext;

/**
 * Suspending uses the generic `update_resource` tool on `users`; the fields'
 * own `suspend` gate decides. Only told to those who may suspend at all.
 */
class SuspendInstructions
{
    public function __invoke(McpContext $context): ?string
    {
        if (! $context->actor->hasPermission('user.suspend')) {
            return null;
        }

        return 'Suspend: update_resource on users with suspendedUntil (ISO 8601 date; null lifts it), suspendReason (for moderators) and suspendMessage (shown to them).';
    }
}
