<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Mcp\Capability\CapabilityRegistry;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Prompt;
use Flarum\Mcp\Capability\Resource;
use Flarum\Mcp\Capability\ResourceTemplate;
use Flarum\Mcp\Capability\Tool;
use Illuminate\Contracts\Container\Container;

/**
 * Registers MCP tools, resources and prompts, and shapes what the generic
 * JSON:API tools expose.
 *
 * Wrap it in `(new Extend\Conditional())->whenExtensionEnabled('flarum-mcp', ...)`
 * so flarum/mcp stays an optional dependency. Removing something twice, or
 * extending a tool that does not exist, is a no-op.
 */
class Mcp implements ExtenderInterface
{
    /**
     * @var list<callable(CapabilityRegistry, ?string): void>
     */
    protected array $operations = [];

    /**
     * @param class-string<Tool> $class
     */
    public function tool(string $class): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addTool($class, $extension);

        return $this;
    }

    /**
     * @param class-string<Resource> $class
     */
    public function resource(string $class): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addResource($class, $extension);

        return $this;
    }

    /**
     * @param class-string<ResourceTemplate> $class
     */
    public function resourceTemplate(string $class): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addResourceTemplate($class, $extension);

        return $this;
    }

    /**
     * @param class-string<Prompt> $class
     */
    public function prompt(string $class): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addPrompt($class, $extension);

        return $this;
    }

    public function removeTool(string $name): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->removeTool($name);

        return $this;
    }

    /**
     * Removes a resource or resource template, by name or by URI (template).
     */
    public function removeResource(string $nameOrUri): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->removeResource($nameOrUri);

        return $this;
    }

    public function removePrompt(string $name): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->removePrompt($name);

        return $this;
    }

    /**
     * Add arguments to another tool. `schema` receives and returns the input
     * schema. `handler` receives the arguments, the API query (or body) the
     * tool built, and the context, and returns the query (or body) to use.
     *
     * @param (callable(array<string, mixed>): array<string, mixed>)|null $schema
     * @param (callable(array<string, mixed>, array<string, mixed>, McpContext): array<string, mixed>)|null $handler
     */
    public function extendTool(string $name, ?callable $schema = null, ?callable $handler = null): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->extendTool($name, $schema, $handler);

        return $this;
    }

    /**
     * Describe a JSON:API resource type for `describe_api` and the overview.
     */
    public function describe(string $type, string $description): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->describe($type, $description);

        return $this;
    }

    /**
     * Let the generic tools write to a type. Reads are always exposed.
     *
     * @param list<'create'|'update'|'delete'> $operations
     */
    public function allowWrites(string $type, array $operations = ['create', 'update', 'delete']): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->allowWrites($type, $operations);

        return $this;
    }

    /**
     * Hide a type from the generic tools and `describe_api`.
     */
    public function hide(string $type): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->hide($type);

        return $this;
    }

    public function hideEndpoint(string $type, string $endpoint): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->hideEndpoint($type, $endpoint);

        return $this;
    }

    /**
     * Expose a custom (non-CRUD) JSON:API endpoint as a tool. It is read-only
     * when the endpoint is a GET, and a write otherwise.
     */
    public function endpointTool(string $type, string $endpoint, string $name, string $title, string $description): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addEndpointTool(
            $type,
            $endpoint,
            $name,
            $title,
            $description,
            $extension
        );

        return $this;
    }

    /**
     * Add a global ability to the "what you can do" list of the overview.
     */
    public function ability(string $ability, string $label): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->addAbility($ability, $label);

        return $this;
    }

    /**
     * Guidance for the model about this extension.
     *
     * @param string|(callable(McpContext): ?string) $instructions
     */
    public function instructions(string|callable $instructions): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry, ?string $extension) => $registry->addInstructions($instructions, $extension);

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        // The registry is bound here rather than in a service provider, so an
        // extension's extender works whichever order the extensions boot in.
        $container->singletonIf(CapabilityRegistry::class);

        $extensionId = $extension?->getId();

        $container->extend(CapabilityRegistry::class, function (CapabilityRegistry $registry) use ($extensionId) {
            foreach ($this->operations as $operation) {
                $operation($registry, $extensionId);
            }

            return $registry;
        });
    }
}
