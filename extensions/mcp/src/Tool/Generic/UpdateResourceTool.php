<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Capability\McpContext;

class UpdateResourceTool extends GenericTool
{
    protected function operation(): string
    {
        return 'update';
    }

    public function name(): string
    {
        return 'update_resource';
    }

    public function title(): string
    {
        return 'Update a resource';
    }

    public function description(): string
    {
        return 'Change attributes or relationships of a resource. This changes the forum. describe_api lists what is writable.';
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
                'type' => $this->typeSchema(),
                'id' => ['type' => 'string'],
                'attributes' => ['type' => 'object', 'description' => 'Attribute values by name.'],
                'relationships' => [
                    'type' => 'object',
                    'description' => 'Related resources by relationship name: an ID, a list of IDs, or {"type", "id"} objects when the relationship can hold more than one type.',
                ],
            ],
            'required' => ['type', 'id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $type = (string) $arguments['type'];
        $resource = $this->exposedResource($type, $context);
        $attributes = (array) ($arguments['attributes'] ?? []);
        $relationships = (array) ($arguments['relationships'] ?? []);

        $this->assertFieldsWritable($type, $attributes, $relationships);

        return $context->api->update(
            $type,
            (string) $arguments['id'],
            $attributes,
            $this->relationships($resource, $relationships),
        )->simplify();
    }
}
