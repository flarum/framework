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
 * Throws from whichever method {@see $throwFrom} names, or returns an input
 * schema the SDK refuses when it is `invalidSchema`.
 */
class ThrowingTool extends Tool
{
    public static string $throwFrom = '';

    protected function maybeThrow(string $method): void
    {
        if (static::$throwFrom === $method) {
            throw new \RuntimeException("ThrowingTool::$method() broke.");
        }
    }

    public function name(): string
    {
        $this->maybeThrow('name');

        return 'throwing';
    }

    public function title(): string
    {
        $this->maybeThrow('title');

        return 'Throwing';
    }

    public function description(): string
    {
        $this->maybeThrow('description');

        return 'Breaks on purpose.';
    }

    public function inputSchema(): array
    {
        $this->maybeThrow('inputSchema');

        return static::$throwFrom === 'invalidSchema' ? ['type' => 'array'] : ['type' => 'object'];
    }

    public function outputSchema(): ?array
    {
        $this->maybeThrow('outputSchema');

        return null;
    }

    public function readOnly(): bool
    {
        $this->maybeThrow('readOnly');

        return true;
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return 'ran';
    }
}
