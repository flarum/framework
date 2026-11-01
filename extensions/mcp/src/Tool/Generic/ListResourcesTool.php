<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Capability\McpContext;

class ListResourcesTool extends GenericTool
{
    public const int MAX_LIMIT = 50;

    protected function operation(): string
    {
        return 'index';
    }

    public function name(): string
    {
        return 'list_resources';
    }

    public function title(): string
    {
        return 'List resources';
    }

    public function description(): string
    {
        return 'List resources of a JSON:API type the forum exposes, with optional filters, sort, paging and included relationships. '
            .'Call describe_api first to learn the filters and sorts a type supports.';
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
                'filter' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string'],
                    'description' => 'Filters by key, e.g. {"q": "search words", "author": "username"}. Prefix a key with "-" to negate it.',
                ],
                'sort' => ['type' => 'string', 'description' => 'A sort name, "-" prefixed for descending, e.g. "-createdAt".'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT],
                'offset' => ['type' => 'integer', 'minimum' => 0],
                'include' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Relationships to include, e.g. ["user"].'],
            ],
            'required' => ['type'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $type = (string) $arguments['type'];
        $this->exposedResource($type, $context);

        $query = $this->query($arguments);

        if (! empty($arguments['filter'])) {
            $query['filter'] = array_map('strval', (array) $arguments['filter']);
        }

        if (! empty($arguments['sort'])) {
            $query['sort'] = (string) $arguments['sort'];
        }

        $query['page'] = array_filter([
            'limit' => min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20)),
            'offset' => max(0, (int) ($arguments['offset'] ?? 0)),
        ], fn (int $value) => $value > 0);

        return $context->api->index($type, $query)->simplify();
    }
}
