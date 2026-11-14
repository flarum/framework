<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend\Fixtures;

use Flarum\Mcp\Capability\McpContext;

class ObjectResultTool extends StructuredResultTool
{
    public function name(): string
    {
        return 'object_result';
    }

    public function outputSchema(): ?array
    {
        return ['type' => 'object'];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        return $arguments['value'] === 'empty-object' ? new \stdClass() : $arguments['value'];
    }
}
