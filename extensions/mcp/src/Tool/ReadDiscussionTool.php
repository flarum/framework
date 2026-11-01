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
use Flarum\Mcp\Tool\Support\DiscussionReader;

class ReadDiscussionTool extends Tool
{
    public function __construct(
        protected DiscussionReader $reader,
    ) {
    }

    public function name(): string
    {
        return 'read_discussion';
    }

    public function title(): string
    {
        return 'Read a discussion';
    }

    public function description(): string
    {
        return 'Read a discussion: its details and a page of posts as plain text, in order. '
            .'Use "near" with a post number to jump there, and pass the paging hints of the result (previous, next) as arguments to continue.';
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => ['string', 'integer'], 'description' => 'The discussion ID.'],
                'near' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Start around this post number.'],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Start after this many posts; the paging hints give it. Takes precedence over "near".'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => DiscussionReader::MAX_LIMIT, 'default' => 20],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return $this->reader->read(
            $context,
            (string) $arguments['id'],
            isset($arguments['near']) ? (int) $arguments['near'] : null,
            (int) ($arguments['limit'] ?? 20),
            isset($arguments['offset']) ? (int) $arguments['offset'] : null,
        );
    }
}
