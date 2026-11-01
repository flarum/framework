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
use Flarum\Mcp\Tool\Support\UserReader;

class GetUserTool extends Tool
{
    public function __construct(
        protected UserReader $reader,
    ) {
    }

    public function name(): string
    {
        return 'get_user';
    }

    public function title(): string
    {
        return 'Get a user profile';
    }

    public function description(): string
    {
        return 'Look up a member by username or ID: display name, groups, join date, activity counts and any other profile fields you may see.';
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
                'username' => ['type' => 'string'],
                'id' => ['type' => ['string', 'integer']],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        if (! empty($arguments['username'])) {
            return $this->reader->read($context, username: (string) $arguments['username']);
        }

        if (isset($arguments['id']) && $arguments['id'] !== '') {
            return $this->reader->read($context, id: (string) $arguments['id']);
        }

        throw new ToolError('Give a username or an id.');
    }
}
