<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Api\HtmlToText;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Extensions add arguments through `Extend\Mcp::extendTool('search_discussions')`;
 * the handler hook receives and returns the `discussions` index query.
 */
class SearchDiscussionsTool extends Tool
{
    public const int MAX_LIMIT = 50;
    public const int EXCERPT_LENGTH = 300;

    protected const array SORTS = [
        'latest' => '-lastPostedAt',
        'top' => '-commentCount',
        'newest' => '-createdAt',
        'oldest' => 'createdAt',
    ];

    public function name(): string
    {
        return 'search_discussions';
    }

    public function title(): string
    {
        return 'Search discussions';
    }

    public function description(): string
    {
        return 'Find discussions by words, author and more. Without a query, lists the latest discussions. '
            .'Returns each discussion\'s ID, title, URL, author, dates, reply count and, when available, an excerpt. Use read_discussion to read one.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Words to search for in titles and posts.'],
                'author' => ['type' => 'string', 'description' => 'Only discussions started by this username.'],
                'sort' => [
                    'type' => 'string',
                    'enum' => array_keys(static::SORTS),
                    'description' => 'Defaults to relevance when there is a query, else latest activity.',
                ],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => 20],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $searchQuery = trim((string) ($arguments['query'] ?? ''));
        $offset = max(0, (int) ($arguments['offset'] ?? 0));

        $filter = [];
        if ($searchQuery !== '') {
            $filter['q'] = $searchQuery;
        }
        if (! empty($arguments['author'])) {
            $filter['author'] = (string) $arguments['author'];
        }

        $query = [
            'filter' => $filter,
            'page' => ['limit' => max(1, min(static::MAX_LIMIT, (int) ($arguments['limit'] ?? 20))), 'offset' => $offset],
            // Only a full-text search has a most relevant post; otherwise the first post stands in.
            'include' => $searchQuery !== '' ? 'user,mostRelevantPost' : 'user,firstPost',
        ];

        if (! empty($arguments['sort']) && isset(static::SORTS[$arguments['sort']])) {
            $query['sort'] = static::SORTS[$arguments['sort']];
        }

        $query = $this->applyHandlerExtenders($arguments, $query, $context);
        $query['filter'] = array_filter((array) ($query['filter'] ?? []), fn ($value) => $value !== null && $value !== '');

        if (! $query['filter']) {
            unset($query['filter']);
        }

        $document = Document::from($result = $context->api->index('discussions', $query));

        $discussions = [];
        foreach ($document->many() as $discussion) {
            $post = $document->includedFor($discussion, 'mostRelevantPost') ?? $document->includedFor($discussion, 'firstPost');
            $html = $post ? Document::attribute($post, 'contentHtml') : null;
            $slug = (string) (Document::attribute($discussion, 'slug') ?: $discussion['id']);

            $discussions[] = array_filter([
                'id' => (string) $discussion['id'],
                'title' => Document::attribute($discussion, 'title'),
                'url' => $context->url->to('forum')->route('discussion', ['id' => $slug]),
                'author' => $document->username($discussion),
                'createdAt' => Document::attribute($discussion, 'createdAt'),
                'lastPostedAt' => Document::attribute($discussion, 'lastPostedAt'),
                'commentCount' => Document::attribute($discussion, 'commentCount'),
                'excerpt' => is_string($html) ? ApiResult::truncate(HtmlToText::convert($html), static::EXCERPT_LENGTH) : null,
            ], fn ($value) => $value !== null);
        }

        $meta = array_filter([
            'total' => $result->meta()['page']['total'] ?? null,
            'next' => isset($result->links()['next']) ? ['offset' => $offset + count($discussions)] : null,
        ], fn ($value) => $value !== null);

        return ApiResult::fit(['data' => $discussions] + ($meta ? ['meta' => $meta] : []), $offset);
    }
}
