<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Flags;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Flags a post for the moderators, as the post's "Flag" button does. Who may
 * flag what is decided by flarum/flags' own endpoint and policies.
 */
class FlagPostTool extends Tool
{
    /**
     * The reasons the forum's flag dialog offers; "other" needs a detail.
     */
    public const array REASONS = ['off_topic', 'inappropriate', 'spam', 'other'];

    public function name(): string
    {
        return 'flag_post';
    }

    public function title(): string
    {
        return 'Flag a post';
    }

    public function description(): string
    {
        return 'Flag a post for the moderators: off topic, inappropriate, spam, or other (explained in detail). '
            .'Moderators see who flagged it. Confirm the post and the reason with the user first.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'postId' => ['type' => ['string', 'integer'], 'description' => 'The post ID (read_discussion lists them).'],
                'reason' => ['type' => 'string', 'enum' => static::REASONS],
                'detail' => ['type' => 'string', 'description' => 'More about the problem. Required when the reason is other.'],
            ],
            'required' => ['postId', 'reason'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $reason = (string) $arguments['reason'];
        $detail = trim((string) ($arguments['detail'] ?? ''));

        if ($reason === 'other' && $detail === '') {
            throw new ToolError('Explain the problem in detail when the reason is other.');
        }

        $flag = Document::from($context->api->create(
            'flags',
            // The forum stores "other" as no reason, with the detail.
            ['reason' => $reason === 'other' ? null : $reason, 'reasonDetail' => $detail !== '' ? $detail : null],
            ['post' => ['data' => ['type' => 'posts', 'id' => (string) $arguments['postId']]]],
        ))->one();

        return ['flagged' => true, 'flagId' => (string) $flag['id'], 'postId' => (string) $arguments['postId']];
    }
}
