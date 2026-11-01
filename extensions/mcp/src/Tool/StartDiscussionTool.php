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

/**
 * Extensions add arguments through `Extend\Mcp::extendTool('start_discussion')`;
 * the handler hook receives and returns `['attributes' => ..., 'relationships' => ...]`.
 */
class StartDiscussionTool extends Tool
{
    public function name(): string
    {
        return 'start_discussion';
    }

    public function title(): string
    {
        return 'Start a discussion';
    }

    public function description(): string
    {
        return 'Start a new discussion as you. This posts publicly on the forum: confirm the title and content with the user first. '
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
                'title' => ['type' => 'string', 'minLength' => 1],
                'content' => ['type' => 'string', 'minLength' => 1, 'description' => 'The first post.'],
            ],
            'required' => ['title', 'content'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $body = $this->applyHandlerExtenders($arguments, [
            'attributes' => [
                'title' => (string) $arguments['title'],
                'content' => (string) $arguments['content'],
            ],
            'relationships' => [],
        ], $context);

        $discussion = Document::from($context->api->create(
            'discussions',
            (array) ($body['attributes'] ?? []),
            (array) ($body['relationships'] ?? []),
        ))->one();

        $slug = (string) (Document::attribute($discussion, 'slug') ?: $discussion['id']);

        return [
            'id' => (string) $discussion['id'],
            'title' => Document::attribute($discussion, 'title'),
            'url' => $context->url->to('forum')->route('discussion', ['id' => $slug]),
        ];
    }
}
