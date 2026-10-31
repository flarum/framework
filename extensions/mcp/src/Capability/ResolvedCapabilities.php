<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

/**
 * The capabilities one actor sees during one MCP request.
 */
class ResolvedCapabilities
{
    /**
     * @param array<string, Tool> $tools keyed by name
     * @param array<string, Resource> $resources keyed by URI
     * @param array<string, ResourceTemplate> $resourceTemplates keyed by URI template
     * @param array<string, Prompt> $prompts keyed by name
     * @param array<string, ?string> $provenance keyed by "tool:name", "resource:uri", "template:uri" or "prompt:name"; the value is the contributing extension's ID
     * @param array<string, bool> $readOnly each tool's readOnly(), as read when it was resolved
     */
    public function __construct(
        public readonly array $tools,
        public readonly array $resources,
        public readonly array $resourceTemplates,
        public readonly array $prompts,
        public readonly array $provenance,
        protected readonly array $readOnly = [],
    ) {
    }

    public function tool(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * False for an unknown tool.
     */
    public function isReadOnly(string $name): bool
    {
        return $this->readOnly[$name] ?? false;
    }

    /**
     * @return array{tools: list<string>, resources: list<string>, prompts: list<string>}
     */
    public function contributedBy(string $extensionId): array
    {
        $contributed = ['tools' => [], 'resources' => [], 'prompts' => []];

        foreach ($this->provenance as $key => $extension) {
            if ($extension !== $extensionId) {
                continue;
            }

            [$kind, $id] = explode(':', $key, 2);

            match ($kind) {
                'tool' => $contributed['tools'][] = $id,
                'resource', 'template' => $contributed['resources'][] = $id,
                'prompt' => $contributed['prompts'][] = $id,
                default => null,
            };
        }

        return $contributed;
    }
}
