<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Messages;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;
use Flarum\User\User;

class ListConversationsTool extends Tool
{
    public const int MAX_LIMIT = 50;

    public function name(): string
    {
        return 'list_conversations';
    }

    public function title(): string
    {
        return 'List my private conversations';
    }

    public function description(): string
    {
        return 'Your private conversations, latest activity first: who they are with, unread count and the latest message. Use read_conversation to read one.';
    }

    public function isAvailableFor(User $actor): bool
    {
        return ! $actor->isGuest();
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'unread' => ['type' => 'boolean', 'description' => 'Only conversations with unread messages.'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $offset = max(0, (int) ($arguments['offset'] ?? 0));

        $query = [
            'page' => ['limit' => max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20))), 'offset' => $offset],
            'include' => 'users,lastMessage,lastMessage.user',
        ];

        if (! empty($arguments['unread'])) {
            $query['filter'] = ['unread' => '1'];
        }

        $document = Document::from($result = $context->api->index('dialogs', $query));

        $conversations = [];
        foreach ($document->many() as $dialog) {
            $conversation = Conversations::conversation($document, $dialog, $context);
            $last = $document->includedFor($dialog, 'lastMessage');

            if ($last) {
                $conversation['lastMessage'] = Conversations::message($document, $last, Conversations::EXCERPT_LENGTH);
            }

            $conversations[] = $conversation;
        }

        $meta = isset($result->links()['next']) ? ['next' => ['offset' => $offset + count($conversations)]] : [];

        return ApiResult::fit(['data' => $conversations] + ($meta ? ['meta' => $meta] : []), $offset);
    }
}
