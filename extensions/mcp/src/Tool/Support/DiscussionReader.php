<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Support;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Api\HtmlToText;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Server\Json;

/**
 * The output of `read_discussion` and `flarum://discussions/{id}`: discussion
 * metadata plus a page of its posts, comments as plain text and events as
 * one-line summaries.
 *
 * @internal
 */
class DiscussionReader
{
    public const int MAX_LIMIT = 50;

    /**
     * @return array<string, mixed>
     */
    public function read(McpContext $context, string $id, ?int $near = null, int $limit = 20): array
    {
        $limit = max(1, min(static::MAX_LIMIT, $limit));

        $discussionDocument = Document::from($context->api->show('discussions', $id, ['include' => 'user']));
        $discussion = $discussionDocument->one();
        $slug = (string) (Document::attribute($discussion, 'slug') ?: $discussion['id']);
        $url = $context->url->to('forum')->route('discussion', ['id' => $slug]);

        $page = ['limit' => $limit];
        if ($near && $near > 1) {
            $page['near'] = $near;
        }

        $postsDocument = Document::from($context->api->index('posts', [
            'filter' => ['discussion' => (string) $discussion['id']],
            'page' => $page,
            'include' => 'user',
        ]));

        $posts = array_map(fn (array $post) => $this->post($postsDocument, $post, $url), $postsDocument->many());

        $lastPostNumber = (int) Document::attribute($discussion, 'lastPostNumber');

        $output = [
            'discussion' => array_filter([
                'id' => (string) $discussion['id'],
                'title' => Document::attribute($discussion, 'title'),
                'url' => $url,
                'author' => $discussionDocument->username($discussion),
                'createdAt' => Document::attribute($discussion, 'createdAt'),
                'lastPostedAt' => Document::attribute($discussion, 'lastPostedAt'),
                'commentCount' => Document::attribute($discussion, 'commentCount'),
                'lastPostNumber' => $lastPostNumber ?: null,
                'canReply' => Document::attribute($discussion, 'canReply'),
                'isLocked' => Document::attribute($discussion, 'isLocked'),
                'isSticky' => Document::attribute($discussion, 'isSticky'),
            ], fn ($value) => $value !== null),
            'posts' => $posts,
        ];

        $output = ApiResult::fit($output, listKey: 'posts');
        $shown = $output['posts'];

        $first = $shown ? (int) $shown[0]['number'] : null;
        $last = $shown ? (int) $shown[count($shown) - 1]['number'] : null;

        $truncated = (bool) ($output['meta']['truncated'] ?? false);
        unset($output['meta']);

        $output['paging'] = [
            'truncated' => $truncated,
            'from' => $first,
            'to' => $last,
            'previous' => $first && $first > 1 ? ['near' => max(1, $first - $limit)] : null,
            'next' => $last && $last < $lastPostNumber ? ['near' => $last + 1] : null,
        ];

        return $output;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    protected function post(Document $document, array $post, string $discussionUrl): array
    {
        $number = (int) Document::attribute($post, 'number');
        $type = (string) Document::attribute($post, 'contentType');
        $author = $document->username($post);

        if ($type !== 'comment') {
            $content = Document::attribute($post, 'content');

            return [
                'number' => $number,
                'id' => (string) $post['id'],
                'event' => trim(($author ?? 'Someone').' '.$type.(is_array($content) && $content ? ': '.Json::encode($content) : '')),
                'createdAt' => Document::attribute($post, 'createdAt'),
            ];
        }

        $html = Document::attribute($post, 'contentHtml');

        return array_filter([
            'number' => $number,
            'id' => (string) $post['id'],
            'author' => $author,
            'createdAt' => Document::attribute($post, 'createdAt'),
            'editedAt' => Document::attribute($post, 'editedAt'),
            'isHidden' => Document::attribute($post, 'isHidden') ?: null,
            'content' => ApiResult::truncate(is_string($html) ? HtmlToText::convert($html) : (string) Document::attribute($post, 'content')),
            'url' => $discussionUrl.'/'.$number,
        ], fn ($value) => $value !== null);
    }
}
