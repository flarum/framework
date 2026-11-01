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

class ReplyToDiscussionTool extends Tool
{
    public function name(): string
    {
        return 'reply_to_discussion';
    }

    public function title(): string
    {
        return 'Reply to a discussion';
    }

    public function description(): string
    {
        return 'Post a reply in a discussion as you. This posts publicly: confirm the content with the user first. '
            .'Content uses the forum\'s formatting (Markdown or BBCode, as enabled).';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function idempotent(): bool
    {
        return false;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'discussionId' => ['type' => ['string', 'integer']],
                'content' => ['type' => 'string', 'minLength' => 1],
            ],
            'required' => ['discussionId', 'content'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $discussionId = (string) $arguments['discussionId'];

        $document = Document::from($context->api->create(
            'posts',
            ['content' => (string) $arguments['content']],
            ['discussion' => ['data' => ['type' => 'discussions', 'id' => $discussionId]]],
            ['include' => 'discussion'],
        ));

        $post = $document->one();
        $discussion = $document->includedFor($post, 'discussion');
        $slug = (string) (($discussion ? Document::attribute($discussion, 'slug') : null) ?: $discussionId);
        $number = (int) Document::attribute($post, 'number');

        return [
            'id' => (string) $post['id'],
            'discussionId' => $discussionId,
            'number' => $number,
            'url' => $context->url->to('forum')->route('discussion', ['id' => $slug]).'/'.$number,
        ];
    }
}
