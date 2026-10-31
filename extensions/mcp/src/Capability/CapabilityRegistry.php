<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

/**
 * Everything registered through {@see \Flarum\Mcp\Extend\Mcp}, as written at
 * boot. It holds configuration only: instances are built per request by
 * {@see CapabilityResolver}, so nothing here varies with the actor.
 */
class CapabilityRegistry
{
    /**
     * @var list<array{class: class-string<Tool>, extension: ?string}>
     */
    protected array $tools = [];

    /**
     * @var list<array{class: class-string<Resource>, extension: ?string}>
     */
    protected array $resources = [];

    /**
     * @var list<array{class: class-string<ResourceTemplate>, extension: ?string}>
     */
    protected array $resourceTemplates = [];

    /**
     * @var list<array{class: class-string<Prompt>, extension: ?string}>
     */
    protected array $prompts = [];

    /**
     * @var array<string, true>
     */
    protected array $removedTools = [];

    /**
     * Resource or resource template names or URIs.
     *
     * @var array<string, true>
     */
    protected array $removedResources = [];

    /**
     * @var array<string, true>
     */
    protected array $removedPrompts = [];

    /**
     * @var array<string, list<array{schema: ?callable, handler: ?callable}>>
     */
    protected array $toolExtensions = [];

    /**
     * @var array<string, string>
     */
    protected array $descriptions = [];

    /**
     * @var array<string, list<string>>
     */
    protected array $writes = [];

    /**
     * @var array<string, true>
     */
    protected array $hiddenTypes = [];

    /**
     * @var array<string, array<string, true>>
     */
    protected array $hiddenEndpoints = [];

    /**
     * @var list<array{type: string, endpoint: string, name: string, title: string, description: string, extension: ?string}>
     */
    protected array $endpointTools = [];

    /**
     * @var array<string, string>
     */
    protected array $abilities = [];

    /**
     * @var list<array{instructions: string|callable, extension: ?string}>
     */
    protected array $instructions = [];

    /**
     * @param class-string<Tool> $class
     */
    public function addTool(string $class, ?string $extension): void
    {
        $this->tools[] = ['class' => $class, 'extension' => $extension];
    }

    /**
     * @param class-string<Resource> $class
     */
    public function addResource(string $class, ?string $extension): void
    {
        $this->resources[] = ['class' => $class, 'extension' => $extension];
    }

    /**
     * @param class-string<ResourceTemplate> $class
     */
    public function addResourceTemplate(string $class, ?string $extension): void
    {
        $this->resourceTemplates[] = ['class' => $class, 'extension' => $extension];
    }

    /**
     * @param class-string<Prompt> $class
     */
    public function addPrompt(string $class, ?string $extension): void
    {
        $this->prompts[] = ['class' => $class, 'extension' => $extension];
    }

    public function removeTool(string $name): void
    {
        $this->removedTools[$name] = true;
    }

    public function removeResource(string $nameOrUri): void
    {
        $this->removedResources[$nameOrUri] = true;
    }

    public function removePrompt(string $name): void
    {
        $this->removedPrompts[$name] = true;
    }

    public function extendTool(string $name, ?callable $schema, ?callable $handler): void
    {
        $this->toolExtensions[$name][] = ['schema' => $schema, 'handler' => $handler];
    }

    public function describe(string $type, string $description): void
    {
        $this->descriptions[$type] = $description;
    }

    /**
     * @param list<string> $operations
     */
    public function allowWrites(string $type, array $operations): void
    {
        $this->writes[$type] = array_values(array_unique([...($this->writes[$type] ?? []), ...$operations]));
    }

    public function hide(string $type): void
    {
        $this->hiddenTypes[$type] = true;
    }

    public function hideEndpoint(string $type, string $endpoint): void
    {
        $this->hiddenEndpoints[$type][$endpoint] = true;
    }

    public function addEndpointTool(string $type, string $endpoint, string $name, string $title, string $description, ?string $extension): void
    {
        $this->endpointTools[] = compact('type', 'endpoint', 'name', 'title', 'description', 'extension');
    }

    public function addAbility(string $ability, string $label): void
    {
        $this->abilities[$ability] = $label;
    }

    public function addInstructions(string|callable $instructions, ?string $extension): void
    {
        $this->instructions[] = ['instructions' => $instructions, 'extension' => $extension];
    }

    /**
     * @return list<array{class: class-string<Tool>, extension: ?string}>
     */
    public function tools(): array
    {
        return $this->tools;
    }

    /**
     * @return list<array{class: class-string<Resource>, extension: ?string}>
     */
    public function resources(): array
    {
        return $this->resources;
    }

    /**
     * @return list<array{class: class-string<ResourceTemplate>, extension: ?string}>
     */
    public function resourceTemplates(): array
    {
        return $this->resourceTemplates;
    }

    /**
     * @return list<array{class: class-string<Prompt>, extension: ?string}>
     */
    public function prompts(): array
    {
        return $this->prompts;
    }

    public function isToolRemoved(string $name): bool
    {
        return isset($this->removedTools[$name]);
    }

    public function isResourceRemoved(string $nameOrUri): bool
    {
        return isset($this->removedResources[$nameOrUri]);
    }

    public function isPromptRemoved(string $name): bool
    {
        return isset($this->removedPrompts[$name]);
    }

    /**
     * @return list<array{schema: ?callable, handler: ?callable}>
     */
    public function toolExtensions(string $name): array
    {
        return $this->toolExtensions[$name] ?? [];
    }

    public function description(string $type): ?string
    {
        return $this->descriptions[$type] ?? null;
    }

    public function writesAllowed(string $type, string $operation): bool
    {
        return in_array($operation, $this->writes[$type] ?? [], true);
    }

    public function isHidden(string $type): bool
    {
        return isset($this->hiddenTypes[$type]);
    }

    public function isEndpointHidden(string $type, string $endpoint): bool
    {
        return isset($this->hiddenEndpoints[$type][$endpoint]);
    }

    /**
     * @return list<array{type: string, endpoint: string, name: string, title: string, description: string, extension: ?string}>
     */
    public function endpointTools(): array
    {
        return $this->endpointTools;
    }

    /**
     * @return array<string, string>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /**
     * @return list<array{instructions: string|callable, extension: ?string}>
     */
    public function instructions(): array
    {
        return $this->instructions;
    }
}
