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
 * Deletes one of the actor's own private messages, when the forum lets members
 * delete theirs (flarum/messages' "allow deleting own messages" setting).
 */
class DeleteMessageTool extends Tool
{
    public function name(): string
    {
        return 'delete_message';
    }

    public function title(): string
    {
        return 'Delete a private message';
    }

    public function description(): string
    {
        return 'Delete one of your own private messages, for every member of the conversation. Whether, and for how long, '
            .'you may delete your messages is up to the forum. This cannot be undone: confirm with the user first.';
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
                'messageId' => ['type' => ['string', 'integer'], 'description' => 'The message ID (read_conversation lists them).'],
            ],
            'required' => ['messageId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $id = (string) $arguments['messageId'];

        $context->api->delete('dialog-messages', $id);

        return ['deleted' => true, 'messageId' => $id];
    }
}
