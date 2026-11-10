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

class StructuredResultTool extends Tool
{
    public function name(): string
    {
        return 'structured_result';
    }

    public function title(): string
    {
        return 'Structured result';
    }

    public function description(): string
    {
        return 'Returns a value described by an output schema.';
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => ['value' => []], 'required' => ['value']];
    }

    public function outputSchema(): ?array
    {
        return ['anyOf' => [
            ['type' => 'object'], ['type' => 'array'], ['type' => 'string'],
            ['type' => 'number'], ['type' => 'boolean'],
        ]];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return $arguments['value'];
    }
}
