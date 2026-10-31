<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

use Flarum\User\User;

/**
 * An MCP resource with a fixed URI. Resolved from the container once per MCP
 * request.
 */
abstract class Resource
{
    abstract public function uri(): string;

    abstract public function name(): string;

    public function title(): ?string
    {
        return null;
    }

    public function description(): ?string
    {
        return null;
    }

    public function mimeType(): string
    {
        return 'application/json';
    }

    public function isAvailableFor(User $actor): bool
    {
        return true;
    }

    /**
     * An array is sent as JSON, a string as is.
     */
    abstract public function read(McpContext $context): mixed;
}
