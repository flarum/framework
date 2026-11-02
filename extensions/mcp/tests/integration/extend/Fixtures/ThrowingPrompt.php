<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend\Fixtures;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Prompt;

class ThrowingPrompt extends Prompt
{
    public function name(): string
    {
        return 'throwing';
    }

    public function arguments(): array
    {
        throw new \RuntimeException('ThrowingPrompt::arguments() broke.');
    }

    public function get(array $arguments, McpContext $context): array|string
    {
        return 'never';
    }
}
