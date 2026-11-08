<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Subscriptions;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Mcp\Tool\Support\Document;

/**
 * Sets the actor's subscription to a discussion, through flarum/subscriptions'
 * writable `subscription` attribute on `discussions`.
 */
class FollowDiscussionTool extends Tool
{
    protected const array STATES = [
        'follow' => 'follow',
        'ignore' => 'ignore',
        'none' => null,
    ];

    public function name(): string
    {
        return 'follow_discussion';
    }

    public function title(): string
    {
        return 'Follow or ignore a discussion';
    }

    public function description(): string
    {
        return 'Set how you follow a discussion: follow (get notified of new replies), ignore (hide it from discussion lists) or none (the default).';
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
                'discussionId' => ['type' => ['string', 'integer']],
                'state' => ['type' => 'string', 'enum' => array_keys(static::STATES), 'default' => 'follow'],
            ],
            'required' => ['discussionId'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $state = (string) ($arguments['state'] ?? 'follow');

        if (! array_key_exists($state, static::STATES)) {
            throw new ToolError('state must be one of: '.implode(', ', array_keys(static::STATES)).'.');
        }

        $discussion = Document::from($context->api->update(
            'discussions',
            (string) $arguments['discussionId'],
            ['subscription' => static::STATES[$state]],
        ))->one();

        // The update response does not load the actor's state, so its
        // `subscription` is empty; the update succeeded, so the state is set.
        return [
            'discussionId' => (string) $discussion['id'],
            'title' => Document::attribute($discussion, 'title'),
            'state' => $state,
        ];
    }
}
