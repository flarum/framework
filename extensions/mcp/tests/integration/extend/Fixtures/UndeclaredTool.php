<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend\Fixtures;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;

/**
 * Does not say whether it writes.
 */
class UndeclaredTool extends Tool
{
    public function name(): string
    {
        return 'undeclared';
    }

    public function title(): string
    {
        return 'Undeclared';
    }

    public function description(): string
    {
        return 'Does not declare readOnly().';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object'];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return 'ran';
    }
}
