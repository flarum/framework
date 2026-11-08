<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Capability\McpContext;

class DeleteResourceTool extends GenericTool
{
    protected function operation(): string
    {
        return 'delete';
    }

    public function name(): string
    {
        return 'delete_resource';
    }

    public function title(): string
    {
        return 'Delete a resource';
    }

    public function description(): string
    {
        return 'Permanently delete a resource. This cannot be undone: confirm with the user first.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function destructive(): bool
    {
        return true;
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
                'type' => $this->typeSchema(),
                'id' => ['type' => 'string'],
            ],
            'required' => ['type', 'id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $type = (string) $arguments['type'];
        $this->exposedResource($type, $context);

        $context->api->delete($type, (string) $arguments['id']);

        return ['deleted' => true, 'type' => $type, 'id' => (string) $arguments['id']];
    }
}
