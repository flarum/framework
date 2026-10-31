<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Tags;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;

/**
 * The tag tree the actor can see: primary tags in order with their children,
 * then the secondary tags, most used first.
 */
class ListTagsTool extends Tool
{
    public function name(): string
    {
        return 'list_tags';
    }

    public function title(): string
    {
        return 'List tags';
    }

    public function description(): string
    {
        return 'The tags discussions are filed under: primary tags in order with their child tags, then secondary tags. '
            .'Each has its slug (for the tag argument of search_discussions and the tags argument of start_discussion), description, discussion count, '
            .'and whether you can start discussions in it.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass()];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $tags = [];
        $children = [];

        foreach (Document::from($context->api->index('tags'))->many() as $resource) {
            $tag = $this->tag($resource, $context);
            $parent = Document::related($resource, 'parent');

            if (Document::attribute($resource, 'isChild') && $parent) {
                $children[$parent['id']][] = $tag;
            } else {
                $tags[] = $tag;
            }
        }

        $primary = [];
        $secondary = [];

        foreach ($tags as $tag) {
            if ($tag['isPrimary']) {
                $tag['children'] = $this->sorted($children[$tag['id']] ?? [], 'position');
                $primary[] = $tag;
            } else {
                $secondary[] = $tag;
            }
        }

        $output = [
            'primary' => $this->sorted($primary, 'position'),
            'secondary' => $this->sorted($secondary, 'discussionCount', descending: true),
        ];

        return ApiResult::fit($output, listKey: 'secondary');
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    protected function tag(array $resource, McpContext $context): array
    {
        $slug = (string) Document::attribute($resource, 'slug');
        $description = Document::attribute($resource, 'description');

        return array_filter([
            'id' => (string) $resource['id'],
            'name' => Document::attribute($resource, 'name'),
            'slug' => $slug,
            'description' => is_string($description) && $description !== '' ? ApiResult::truncate($description) : null,
            'url' => $context->url->to('forum')->route('tag', ['slug' => $slug]),
            'isPrimary' => (bool) Document::attribute($resource, 'isPrimary'),
            'isHidden' => Document::attribute($resource, 'isHidden') ?: null,
            'position' => Document::attribute($resource, 'position'),
            'discussionCount' => (int) Document::attribute($resource, 'discussionCount'),
            'lastPostedAt' => Document::attribute($resource, 'lastPostedAt'),
            'canStartDiscussion' => (bool) Document::attribute($resource, 'canStartDiscussion'),
        ], fn ($value) => $value !== null);
    }

    /**
     * @param list<array<string, mixed>> $tags
     * @return list<array<string, mixed>>
     */
    protected function sorted(array $tags, string $key, bool $descending = false): array
    {
        usort($tags, function (array $a, array $b) use ($key, $descending) {
            $order = ($a[$key] ?? PHP_INT_MAX) <=> ($b[$key] ?? PHP_INT_MAX);

            return $descending ? -$order : $order;
        });

        return $tags;
    }
}
