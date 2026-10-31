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
 * A misconfigured extension's tool: it cannot even be built.
 */
class BrokenTool extends Tool
{
    public function __construct()
    {
        throw new \RuntimeException('Broken on purpose.');
    }

    public function name(): string
    {
        return 'broken';
    }

    public function title(): string
    {
        return 'Broken';
    }

    public function description(): string
    {
        return 'Never listed.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object'];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return null;
    }
}
