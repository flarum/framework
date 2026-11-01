<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Flags;

use Flarum\Flags\Command\DeleteFlags;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\User\User;

/**
 * Dismisses every flag on a post, as a moderator's "Dismiss flags" does. It is
 * a plain route (DELETE /api/posts/{id}/flags), not a JSON:API endpoint; this
 * runs the command it runs.
 */
class DismissFlagsTool extends Tool
{
    public function name(): string
    {
        return 'dismiss_flags';
    }

    public function title(): string
    {
        return 'Dismiss a post\'s flags';
    }

    public function description(): string
    {
        return 'Dismiss every flag on a post, once it has been dealt with or needs no action. The post itself is not changed. '
            .'list_resources on flags lists flagged posts. Confirm with the user first.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function destructive(): bool
    {
        return true;
    }

    public function idempotent(): bool
    {
        return true;
    }

    public function isAvailableFor(User $actor): bool
    {
        return $actor->hasPermission('discussion.viewFlags');
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'postId' => ['type' => ['string', 'integer'], 'description' => 'The flagged post\'s ID.'],
            ],
            'required' => ['postId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $postId = (int) $arguments['postId'];

        $context->api->dispatch(new DeleteFlags($postId, $context->actor));

        return ['dismissed' => true, 'postId' => (string) $postId];
    }
}
