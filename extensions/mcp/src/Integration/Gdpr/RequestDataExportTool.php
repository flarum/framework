<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Gdpr;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\User\User;

/**
 * Requests an export of the actor's own data, as "Export data" in their
 * settings does. The export is built in the background; the member is
 * notified, by email too, when it can be downloaded.
 */
class RequestDataExportTool extends Tool
{
    public function name(): string
    {
        return 'request_data_export';
    }

    public function title(): string
    {
        return 'Request an export of your data';
    }

    public function description(): string
    {
        return 'Ask the forum for a copy of the data it holds about you. It is prepared in the background, and you are notified '
            .'when it is ready to download from the forum. Nothing is returned here.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function isAvailableFor(User $actor): bool
    {
        return ! $actor->isGuest();
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass()];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $context->api->endpoint('gdpr-exports', 'export', 'POST', '/', [], [
            'data' => ['type' => 'gdpr-exports', 'attributes' => ['userId' => $context->actor->id]],
        ]);

        return ['requested' => true];
    }
}
