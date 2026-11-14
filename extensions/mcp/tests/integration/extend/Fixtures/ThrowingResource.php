<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend\Fixtures;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Resource;

class ThrowingResource extends Resource
{
    public function uri(): string
    {
        return 'test://throwing';
    }

    public function name(): string
    {
        return 'throwing';
    }

    public function description(): ?string
    {
        throw new \RuntimeException('ThrowingResource::description() broke.');
    }

    public function read(McpContext $context): mixed
    {
        return [];
    }
}
