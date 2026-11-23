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
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\Document;
use Flarum\User\Exception\PermissionDeniedException;

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
        return 'Change a post you may edit (usually your own). Give "edits" to replace parts of it: each "find" must appear exactly once in the post\'s current source, which get_resource on posts returns in full as "content". Or give "content" to replace the whole post. Confirm the change with the user first.';
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
                'content' => ['type' => 'string', 'minLength' => 1, 'description' => 'The whole new content. Leave out when giving edits.'],
                'edits' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'description' => 'Replacements made in order, each on the result of the one before.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'find' => ['type' => 'string', 'minLength' => 1],
                            'replace' => ['type' => 'string'],
                        ],
                        'required' => ['find', 'replace'],
                    ],
                ],
            ],
            'required' => ['postId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $postId = (string) $arguments['postId'];

        if (! isset($arguments['content']) && ! isset($arguments['edits'])) {
            throw new ToolError('Give "content" or "edits".');
        }

        $content = isset($arguments['edits'])
            ? $this->applyEdits($this->currentSource($postId, $context), $arguments['edits'])
            : (string) $arguments['content'];

        $post = Document::from($context->api->update('posts', $postId, ['content' => $content]))->one();

        return [
            'id' => (string) $post['id'],
            'number' => Document::attribute($post, 'number'),
            'editedAt' => Document::attribute($post, 'editedAt'),
        ];
    }

    protected function currentSource(string $postId, McpContext $context): string
    {
        $post = Document::from($context->api->show('posts', $postId))->one();

        // The source is only sent to those who may edit the post.
        if (Document::attribute($post, 'canEdit') !== true) {
            throw new PermissionDeniedException();
        }

        return (string) Document::attribute($post, 'content');
    }

    /**
     * @param array<int, array{find: string, replace: string}> $edits
     */
    protected function applyEdits(string $content, array $edits): string
    {
        foreach ($edits as $edit) {
            $find = (string) $edit['find'];

            $count = substr_count($content, $find);

            if ($count === 0) {
                throw new ToolError("Not found in the post: \"$find\". Nothing was changed.");
            }

            if ($count > 1) {
                throw new ToolError("Found $count times in the post: \"$find\". Include more of the text around it so it matches once. Nothing was changed.");
            }

            $content = str_replace($find, (string) $edit['replace'], $content);
        }

        return $content;
    }
}
