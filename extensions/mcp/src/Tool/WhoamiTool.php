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
use Flarum\Mcp\Overview\OverviewBuilder;

class WhoamiTool extends Tool
{
    public function __construct(
        protected OverviewBuilder $overview,
    ) {
    }

    public function name(): string
    {
        return 'whoami';
    }

    public function title(): string
    {
        return 'Who am I';
    }

    public function description(): string
    {
        return 'Who you are acting as on this forum: username, display name, groups, what you are allowed to do, and whether you may make changes.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass()];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return [
            ...$this->overview->you($context->actor),
            'scopes' => $context->scopes,
        ];
    }
}
