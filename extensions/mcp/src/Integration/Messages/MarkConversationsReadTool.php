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
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Server\ErrorTranslator;
use Flarum\Mcp\Tool\Support\Document;
use Throwable;

/**
 * Marks private conversations as read, as opening them in the forum does.
 * Reading one with read_conversation leaves it unread.
 */
class MarkConversationsReadTool extends Tool
{
    public const int MAX_IDS = 50;

    public function __construct(
        protected ErrorTranslator $errors,
    ) {
    }

    public function name(): string
    {
        return 'mark_conversations_read';
    }

    public function title(): string
    {
        return 'Mark private conversations as read';
    }

    public function description(): string
    {
        return 'Mark the given private conversations as read up to their latest message, or all of them when no IDs are given. '
            .'Reading a conversation with read_conversation does not mark it read. IDs that cannot be marked are listed under "failed"; the others are still marked.';
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
                'ids' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']], 'maxItems' => static::MAX_IDS],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $ids = array_map('strval', (array) ($arguments['ids'] ?? []));

        if (! $ids) {
            $context->api->endpoint('dialogs', 'read', 'POST', '/read');

            return ['marked' => 'all'];
        }

        // One bad ID (gone, or someone else's) does not stop the rest.
        $marked = [];
        $failed = [];

        foreach ($ids as $id) {
            try {
                $dialog = Document::from($context->api->show('dialogs', $id))->one();
                $context->api->update('dialogs', $id, ['lastReadMessageId' => (int) Document::attribute($dialog, 'lastMessageId')]);
                $marked[] = $id;
            } catch (Throwable $e) {
                $message = $e instanceof ToolError ? null : $this->errors->translate($e);

                if ($message === null) {
                    throw $e;
                }

                $failed[] = ['id' => $id, 'error' => $message];
            }
        }

        if (! $marked) {
            throw new ToolError(implode("\n", array_map(fn (array $failure) => "{$failure['id']}: {$failure['error']}", $failed)));
        }

        return array_filter(['marked' => $marked, 'failed' => $failed]);
    }
}
