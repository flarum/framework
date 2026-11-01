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
 *
 * What it registers is attributed to the extension whose extend.php applies
 * it, unless an owner is given: `new Mcp('flarum-tags')` registers on behalf
 * of flarum/tags, for an integration that lives in another extension.
 */
class Mcp implements ExtenderInterface
{
    /**
     * @var list<callable(CapabilityRegistry, ?string): void>
     */
    protected array $operations = [];

    /**
     * @param string|null $extensionId the extension the registrations belong to, instead of the one applying the extender
     */
    public function __construct(
        protected ?string $extensionId = null,
    ) {
    }

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
     * Refuse fields of a type to the generic create and update tools, whatever
     * the actor's own gates allow, for fields an assistant must never change
     * (credentials, privileges). A call naming one is refused before it
     * reaches the API, and `describe_api` reports the field as not writable.
     *
     * @param list<string> $attributes
     * @param list<string> $relationships
     */
    public function denyWriteFields(string $type, array $attributes, array $relationships = []): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->denyWriteFields($type, $attributes, $relationships);

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

    /**
     * Accept /mcp requests whose `Host` header names one of these host names,
     * besides the forum's own (for a forum reached under several names).
     * Anything else is refused, against DNS rebinding.
     *
     * @param list<string> $hosts host names without port, e.g. `['forum.example.org']`
     */
    public function allowHosts(array $hosts): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->allowHosts($hosts);

        return $this;
    }

    /**
     * Let browser pages on these origins call /mcp (CORS). By default only
     * same-origin pages and non-browser clients can.
     *
     * @param list<string> $origins e.g. `['https://app.example.com']`
     */
    public function allowOrigins(array $origins): self
    {
        $this->operations[] = fn (CapabilityRegistry $registry) => $registry->allowOrigins($origins);

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        // The registry is bound here rather than in a service provider, so an
        // extension's extender works whichever order the extensions boot in.
        $container->singletonIf(CapabilityRegistry::class);

        $extensionId = $this->extensionId ?? $extension?->getId();

        $container->extend(CapabilityRegistry::class, function (CapabilityRegistry $registry) use ($extensionId) {
            foreach ($this->operations as $operation) {
                $operation($registry, $extensionId);
            }

            return $registry;
        });
    }
}
