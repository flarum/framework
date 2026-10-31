<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Capability\McpContext;

class CreateResourceTool extends GenericTool
{
    protected function operation(): string
    {
        return 'create';
    }

    public function name(): string
    {
        return 'create_resource';
    }

    public function title(): string
    {
        return 'Create a resource';
    }

    public function description(): string
    {
        return 'Create a resource of a JSON:API type. This changes the forum. describe_api lists the writable attributes and relationships.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => $this->typeSchema(),
                'attributes' => ['type' => 'object', 'description' => 'Attribute values by name.'],
                'relationships' => [
                    'type' => 'object',
                    'description' => 'Related resources by relationship name: an ID, a list of IDs, or {"type", "id"} objects when the relationship can hold more than one type.',
                ],
            ],
            'required' => ['type'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $type = (string) $arguments['type'];
        $resource = $this->exposedResource($type, $context);

        return $context->api->create(
            $type,
            (array) ($arguments['attributes'] ?? []),
            $this->relationships($resource, (array) ($arguments['relationships'] ?? [])),
        )->simplify();
    }
}
