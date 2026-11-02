<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Likes;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Likes or unlikes a post as the actor, through flarum/likes' writable
 * `isLiked` attribute on `posts`; its `like` gate decides.
 */
abstract class LikeTool extends Tool
{
    abstract protected function liked(): bool;

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
                'postId' => ['type' => ['string', 'integer'], 'description' => 'The post ID (read_discussion lists them).'],
            ],
            'required' => ['postId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $post = Document::from($context->api->update(
            'posts',
            (string) $arguments['postId'],
            ['isLiked' => $this->liked()],
        ))->one();

        return array_filter([
            'postId' => (string) $post['id'],
            'liked' => $this->liked(),
            'likesCount' => Document::attribute($post, 'likesCount'),
        ], fn ($value) => $value !== null);
    }
}
