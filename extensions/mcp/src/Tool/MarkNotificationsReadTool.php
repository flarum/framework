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
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Server\ErrorTranslator;
use Flarum\Notification\Command\ReadAllNotifications;
use Throwable;

class MarkNotificationsReadTool extends Tool
{
    public const int MAX_IDS = 50;

    public function __construct(
        protected ErrorTranslator $errors,
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
        return 'Mark the given notifications as read, or all of them when no IDs are given. IDs that cannot be marked are listed under "failed"; the others are still marked.';
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
            // "Read all" is a plain controller (POST /api/notifications/read), not a
            // JSON:API endpoint; this is the command it runs, through the API client
            // so the write rule applies.
            $context->api->dispatch(new ReadAllNotifications($context->actor));

            return ['marked' => 'all'];
        }

        // One bad ID (gone, or someone else's) does not stop the rest.
        $marked = [];
        $failed = [];

        foreach ($ids as $id) {
            try {
                $context->api->update('notifications', $id, ['isRead' => true]);
                $marked[] = $id;
            } catch (Throwable $e) {
                // A refused write (no write scope, a guest) refuses every ID alike;
                // an unknown error is a defect, reported by the invoker.
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
