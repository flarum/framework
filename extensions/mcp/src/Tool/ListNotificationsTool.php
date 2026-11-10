<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool;

use DateTimeInterface;
use Flarum\Api\JsonApi;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Notification\NotificationRepository;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Reads notifications without marking them seen. The `notifications` Index
 * endpoint cannot be used: it sets the actor's `read_notifications_at` (the
 * forum's "seen" badge) before listing, so this reads through the repository
 * that endpoint's searcher uses, with the same grouping and subject visibility,
 * and shapes each notification as the API serialises it.
 */
class ListNotificationsTool extends Tool
{
    public const int MAX_LIMIT = 50;

    public function __construct(
        protected NotificationRepository $notifications,
        protected JsonApi $api,
    ) {
    }

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
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $actor = $context->actor;
        $limit = max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)));

        $models = $this->notifications->findByUser($actor, $limit);

        // As the API's eager load does, only senders the actor may see.
        $models->load(['fromUser' => fn (Relation $query) => $query->whereVisibleTo($actor)]);

        $notifications = [];
        foreach ($models as $notification) {
            $subjectType = $notification->subjectModel ? $this->api->typeForModel($notification->subjectModel) : null;

            $notifications[] = array_filter([
                'id' => (string) $notification->id,
                'type' => $notification->type,
                'isRead' => (bool) $notification->read_at,
                'createdAt' => $notification->created_at?->format(DateTimeInterface::RFC3339),
                'from' => $notification->fromUser?->username,
                'subject' => $subjectType && $notification->subject_id ? ['type' => $subjectType, 'id' => (string) $notification->subject_id] : null,
                'content' => $notification->data,
            ], fn ($value) => $value !== null);
        }

        // Stable: within each group the newest-first order is kept.
        usort($notifications, fn (array $a, array $b) => $a['isRead'] <=> $b['isRead']);

        return ['data' => $notifications];
    }
}
