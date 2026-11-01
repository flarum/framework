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
use Flarum\Mcp\Tool\Support\Document;

/**
 * Names a group conversation, where the forum lets members name groups
 * (flarum/messages' "allow group names" setting).
 */
class RenameConversationTool extends Tool
{
    public function name(): string
    {
        return 'rename_conversation';
    }

    public function title(): string
    {
        return 'Rename a group conversation';
    }

    public function description(): string
    {
        return 'Give a group conversation a name, or remove its name with an empty one. Its members are told who renamed it. '
            .'Only group conversations can be named, and only where the forum allows it. Confirm the name with the user first.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function idempotent(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'conversationId' => ['type' => ['string', 'integer'], 'description' => 'The conversation ID (list_conversations lists them).'],
                'name' => ['type' => 'string', 'description' => 'The new name; empty to remove it.'],
            ],
            'required' => ['conversationId', 'name'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $id = (string) $arguments['conversationId'];

        $dialog = Document::from($context->api->update('dialogs', $id, ['name' => (string) $arguments['name']]))->one();

        return [
            'conversationId' => $id,
            'name' => Document::attribute($dialog, 'name'),
            'title' => Document::attribute($dialog, 'title'),
        ];
    }
}
