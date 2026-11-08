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
 * An MCP prompt. Resolved from the container once per MCP request.
 */
abstract class Prompt
{
    abstract public function name(): string;

    public function title(): ?string
    {
        return null;
    }

    public function description(): ?string
    {
        return null;
    }

    /**
     * @return array<string, array{description?: string, required?: bool}>
     */
    public function arguments(): array
    {
        return [];
    }

    public function isAvailableFor(User $actor): bool
    {
        return true;
    }

    /**
     * A string becomes one user message.
     *
     * @param array<string, mixed> $arguments
     * @return list<array{role: 'user'|'assistant', text: string}>|string
     */
    abstract public function get(array $arguments, McpContext $context): array|string;
}
