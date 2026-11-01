<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Tags;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\Document;

/**
 * The tag arguments flarum/tags adds to `search_discussions` (`tag`) and
 * `start_discussion` (`tags`), through `Extend\Mcp::extendTool()`.
 */
class TagArguments
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function searchSchema(array $schema): array
    {
        $schema['properties']['tag'] = [
            'type' => 'string',
            'description' => 'Only discussions with this tag, by slug (see list_tags). Use "untagged" for discussions without tags.',
        ];

        return $schema;
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $query the discussions index query
     * @return array<string, mixed>
     */
    public static function searchHandler(array $arguments, array $query, McpContext $context): array
    {
        $tag = trim((string) ($arguments['tag'] ?? ''));

        if ($tag !== '') {
            $query['filter']['tag'] = $tag;
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function startSchema(array $schema): array
    {
        $schema['properties']['tags'] = [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => 'Slugs of the tags to file the discussion under (see list_tags, which also says where you can start discussions). '
                .'The forum may require a number of primary and secondary tags.',
        ];

        return $schema;
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array{attributes?: array<string, mixed>, relationships?: array<string, mixed>} $body
     * @return array<string, mixed>
     */
    public static function startHandler(array $arguments, array $body, McpContext $context): array
    {
        $slugs = array_values(array_unique(array_filter(
            array_map(fn ($slug) => trim((string) $slug), (array) ($arguments['tags'] ?? [])),
            fn (string $slug) => $slug !== ''
        )));

        if (! $slugs) {
            return $body;
        }

        $ids = [];
        foreach (Document::from($context->api->index('tags'))->many() as $tag) {
            $ids[(string) Document::attribute($tag, 'slug')] = (string) $tag['id'];
        }

        $unknown = array_diff($slugs, array_keys($ids));

        if ($unknown) {
            throw new ToolError('Unknown tag(s): '.implode(', ', $unknown).'. Call list_tags for the available slugs.');
        }

        $body['relationships']['tags'] = [
            'data' => array_map(fn (string $slug) => ['type' => 'tags', 'id' => $ids[$slug]], $slugs),
        ];

        return $body;
    }
}
