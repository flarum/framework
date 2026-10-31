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
use Flarum\Mcp\Exception\ToolError;

class EchoTool extends Tool
{
    public function name(): string
    {
        return 'echo';
    }

    public function title(): string
    {
        return 'Echo';
    }

    public function description(): string
    {
        return 'Repeats what you say.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text']];
    }

    public function outputSchema(): ?array
    {
        return ['type' => 'object', 'properties' => ['echo' => ['type' => 'string'], 'by' => ['type' => ['string', 'null']]]];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        if ($arguments['text'] === 'fail') {
            throw new ToolError('Echo refused.');
        }

        if ($arguments['text'] === 'crash') {
            throw new \LogicException('secret internal detail');
        }

        return ['echo' => $arguments['text'], 'by' => $context->actor->username];
    }
}
