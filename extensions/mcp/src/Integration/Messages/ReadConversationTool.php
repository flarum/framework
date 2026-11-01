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
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'How many of the latest messages to skip.'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $id = (string) $arguments['id'];
        $offset = max(0, (int) ($arguments['offset'] ?? 0));
        $limit = max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)));

        $dialogDocument = Document::from($context->api->show('dialogs', $id, ['include' => 'users']));
        $dialog = $dialogDocument->one();

        // Newest first, the endpoint's default order, so that offset 0 is the latest page.
        $messagesDocument = Document::from($result = $context->api->index('dialog-messages', [
            'filter' => ['dialog' => (string) $dialog['id']],
            'page' => ['limit' => $limit, 'offset' => $offset],
            'include' => 'user',
        ]));

        // Still newest first: a page too large is cut from the end, so what
        // is dropped are its oldest messages, and the latest stay contiguous.
        $messages = array_map(
            fn (array $message) => Conversations::message($messagesDocument, $message),
            $messagesDocument->many()
        );

        $output = ApiResult::fit([
            'conversation' => Conversations::conversation($dialogDocument, $dialog, $context),
            'messages' => $messages,
        ], listKey: 'messages');

        $truncated = (bool) ($output['meta']['truncated'] ?? false);
        unset($output['meta']);

        $shown = count($output['messages']);
        $output['messages'] = array_reverse($output['messages']);
        $newerOffset = max(0, $offset - $limit);

        $output['paging'] = [
            'truncated' => $truncated,
            // Continues right after the messages shown, including any the cut dropped.
            'older' => $shown && ($truncated || isset($result->links()['next'])) ? ['offset' => $offset + $shown] : null,
            'newer' => $offset > 0 ? ['offset' => $newerOffset, 'limit' => $offset - $newerOffset] : null,
        ];

        return $output;
    }
}
