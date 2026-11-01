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

/**
 * Reads without marking anything read: the forum does that when its owner
 * opens the conversation.
 */
class ReadConversationTool extends Tool
{
    public const int MAX_LIMIT = 50;

    public function name(): string
    {
        return 'read_conversation';
    }

    public function title(): string
    {
        return 'Read a private conversation';
    }

    public function description(): string
    {
        return 'The messages of one of your private conversations, oldest first. By default the latest messages; '
            .'use the offset from paging.older to go further back.';
    }

    public function readOnly(): bool
    {
        return true;
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
                'id' => ['type' => ['string', 'integer'], 'description' => 'The conversation ID (list_conversations lists them).'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'How many of the latest messages to skip; paging.older gives it.'],
                'before' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Read the messages just newer than this offset; paging.newer gives it. Takes precedence over "offset".'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $id = (string) $arguments['id'];
        $limit = max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)));
        $before = isset($arguments['before']) ? (int) $arguments['before'] : 0;
        $backward = $before > 0;
        $offset = $backward ? max(0, $before - $limit) : max(0, (int) ($arguments['offset'] ?? 0));
        $count = $backward ? $before - $offset : $limit;

        $dialogDocument = Document::from($context->api->show('dialogs', $id, ['include' => 'users']));
        $dialog = $dialogDocument->one();

        // Newest first, the endpoint's default order, so that offset 0 is the latest page.
        $messagesDocument = Document::from($result = $context->api->index('dialog-messages', [
            'filter' => ['dialog' => (string) $dialog['id']],
            'page' => ['limit' => $count, 'offset' => $offset],
            'include' => 'user',
        ]));

        $messages = array_map(
            fn (array $message) => Conversations::message($messagesDocument, $message),
            $messagesDocument->many()
        );

        // A page too large is cut at the end away from the reader: paging
        // older (newest first) drops its oldest messages, paging newer
        // (oldest first) its newest, so the next hint either way starts right
        // where the messages shown stop.
        $output = ApiResult::fit([
            'conversation' => Conversations::conversation($dialogDocument, $dialog, $context),
            'messages' => $backward ? array_reverse($messages) : $messages,
        ], listKey: 'messages');

        $truncated = (bool) ($output['meta']['truncated'] ?? false);
        unset($output['meta']);

        $shown = count($output['messages']);
        if (! $backward) {
            $output['messages'] = array_reverse($output['messages']);
        }

        // Offsets (from the latest) of the newest message shown and of the one right after the oldest.
        $from = $offset + ($backward ? count($messages) - $shown : 0);
        $to = $from + $shown;
        $more = $to < $offset + count($messages) || isset($result->links()['next']);

        $output['paging'] = [
            'truncated' => $truncated,
            'older' => $shown && $more ? ['offset' => $to] : null,
            'newer' => $from > 0 ? ['before' => $from] : null,
        ];

        return $output;
    }
}
