<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Capability\McpContext;

class GetResourceTool extends GenericTool
{
    protected function operation(): string
    {
        return 'show';
    }

    public function name(): string
    {
        return 'get_resource';
    }

    public function title(): string
    {
        return 'Get a resource';
    }

    public function description(): string
    {
        return 'Fetch one resource of a JSON:API type by ID, optionally with included relationships.';
    }

    public function readOnly(): bool
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
                'include' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Relationships to include, e.g. ["user"].'],
            ],
            'required' => ['type', 'id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $type = (string) $arguments['type'];
        $this->exposedResource($type, $context);

        return $context->api->show($type, (string) $arguments['id'], $this->query($arguments))->simplify();
    }
}
