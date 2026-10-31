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
use Flarum\Notification\Command\ReadAllNotifications;
use Illuminate\Contracts\Bus\Dispatcher;

class MarkNotificationsReadTool extends Tool
{
    public const int MAX_IDS = 50;

    public function __construct(
        protected Dispatcher $bus,
    ) {
    }

    public function name(): string
    {
        return 'mark_notifications_read';
    }

    public function title(): string
    {
        return 'Mark notifications as read';
    }

    public function description(): string
    {
        return 'Mark the given notifications as read, or all of them when no IDs are given.';
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
            $context->actor->assertRegistered();

            // "Read all" is a plain controller, not a JSON:API endpoint; this is the command it runs.
            $this->bus->dispatch(new ReadAllNotifications($context->actor));

            return ['marked' => 'all'];
        }

        foreach ($ids as $id) {
            $context->api->update('notifications', $id, ['isRead' => true]);
        }

        return ['marked' => $ids];
    }
}
