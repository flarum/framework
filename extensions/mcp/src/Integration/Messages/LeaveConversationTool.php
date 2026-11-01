<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Messages;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;

/**
 * Leaves a group conversation. A one-to-one conversation can't be left.
 */
class LeaveConversationTool extends Tool
{
    public function name(): string
    {
        return 'leave_conversation';
    }

    public function title(): string
    {
        return 'Leave a private conversation';
    }

    public function description(): string
    {
        return 'Leave a group conversation: you stop receiving its messages and can no longer read it. '
            .'Conversations with one other member cannot be left. Confirm with the user first.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function destructive(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'conversationId' => ['type' => ['string', 'integer'], 'description' => 'The conversation ID (list_conversations lists them).'],
            ],
            'required' => ['conversationId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $id = (string) $arguments['conversationId'];

        $context->api->endpoint('dialogs', 'leave', 'POST', '/'.rawurlencode($id).'/leave');

        return ['left' => true, 'conversationId' => $id];
    }
}
