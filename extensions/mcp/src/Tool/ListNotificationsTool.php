<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;
use Flarum\User\User;

class ListNotificationsTool extends Tool
{
    public const int MAX_LIMIT = 50;

    public function name(): string
    {
        return 'list_notifications';
    }

    public function title(): string
    {
        return 'List my notifications';
    }

    public function description(): string
    {
        return 'Your recent notifications, unread first: what happened, who did it, and what it is about.';
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
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $document = Document::from($context->api->index('notifications', [
            'page' => ['limit' => max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)))],
            'include' => 'fromUser',
        ]));

        $notifications = [];
        foreach ($document->many() as $notification) {
            $notifications[] = array_filter([
                'id' => (string) $notification['id'],
                'type' => Document::attribute($notification, 'contentType'),
                'isRead' => (bool) Document::attribute($notification, 'isRead'),
                'createdAt' => Document::attribute($notification, 'createdAt'),
                'from' => $document->username($notification, 'fromUser'),
                'subject' => Document::related($notification, 'subject'),
                'content' => Document::attribute($notification, 'content'),
            ], fn ($value) => $value !== null);
        }

        // Stable: within each group the API's newest-first order is kept.
        usort($notifications, fn (array $a, array $b) => $a['isRead'] <=> $b['isRead']);

        return ['data' => $notifications];
    }
}
