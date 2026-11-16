<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Tool\Support\Document;

class EditPostTool extends Tool
{
    public function name(): string
    {
        return 'edit_post';
    }

    public function title(): string
    {
        return 'Edit a post';
    }

    public function description(): string
    {
        return 'Replace the content of a post you may edit (usually your own). Confirm the new content with the user first.';
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
                'postId' => ['type' => ['string', 'integer']],
                'content' => ['type' => 'string', 'minLength' => 1],
            ],
            'required' => ['postId', 'content'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $post = Document::from($context->api->update(
            'posts',
            (string) $arguments['postId'],
            ['content' => (string) $arguments['content']],
        ))->one();

        return [
            'id' => (string) $post['id'],
            'number' => Document::attribute($post, 'number'),
            'editedAt' => Document::attribute($post, 'editedAt'),
        ];
    }
}
