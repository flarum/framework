<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

use Flarum\Mcp\Tool\Generic\EndpointTool;
use Flarum\Mcp\Tool\Generic\GenericTool;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;

/**
 * Builds the capability instances an actor sees. Runs once per MCP request.
 *
 * Every call into extension code here is guarded: a capability that fails to
 * build, or whose metadata (name, title, description, schemas, flags) throws,
 * is reported and left out, so one broken extension cannot take the endpoint
 * down. Metadata is read once here; the rest of the request reads what passed.
 */
class CapabilityResolver
{
    public function __construct(
        protected Container $container,
        protected CapabilityRegistry $registry,
        protected FailureReporter $failures,
    ) {
    }

    public function resolve(User $actor): ResolvedCapabilities
    {
        $provenance = [];
        $readOnly = [];

        $tools = [];
        foreach ($this->registry->tools() as $entry) {
            $tool = $this->failures->attempt(fn () => $this->container->make($entry['class']));

            if ($tool instanceof Tool) {
                $this->addTool($tools, $provenance, $readOnly, $tool, $entry['extension'], $actor);
            }
        }

        foreach ($this->registry->endpointTools() as $entry) {
            $tool = $this->failures->attempt(fn () => $this->container->make(EndpointTool::class, [
                'type' => $entry['type'],
                'endpoint' => $entry['endpoint'],
                'toolName' => $entry['name'],
                'toolTitle' => $entry['title'],
                'toolDescription' => $entry['description'],
            ]));

            if ($tool instanceof Tool) {
                $this->addTool($tools, $provenance, $readOnly, $tool, $entry['extension'], $actor);
            }
        }

        $resources = [];
        foreach ($this->registry->resources() as $entry) {
            $resource = $this->failures->attempt(fn () => $this->container->make($entry['class']));

            if (! $resource instanceof Resource || ! $this->probe(fn () => [$resource->uri(), $resource->name(), $resource->title(), $resource->description(), $resource->mimeType()])) {
                continue;
            }

            if ($this->registry->isResourceRemoved($resource->name())
                || $this->registry->isResourceRemoved($resource->uri())
                || ! $this->check(fn () => $resource->isAvailableFor($actor))) {
                continue;
            }

            $resources[$resource->uri()] = $resource;
            $provenance['resource:'.$resource->uri()] = $entry['extension'];
        }

        $templates = [];
        foreach ($this->registry->resourceTemplates() as $entry) {
            $template = $this->failures->attempt(fn () => $this->container->make($entry['class']));

            if (! $template instanceof ResourceTemplate || ! $this->probe(fn () => [$template->uriTemplate(), $template->name(), $template->title(), $template->description(), $template->mimeType()])) {
                continue;
            }

            if ($this->registry->isResourceRemoved($template->name())
                || $this->registry->isResourceRemoved($template->uriTemplate())
                || ! $this->check(fn () => $template->isAvailableFor($actor))) {
                continue;
            }

            $templates[$template->uriTemplate()] = $template;
            $provenance['template:'.$template->uriTemplate()] = $entry['extension'];
        }

        $prompts = [];
        foreach ($this->registry->prompts() as $entry) {
            $prompt = $this->failures->attempt(fn () => $this->container->make($entry['class']));

            if (! $prompt instanceof Prompt || ! $this->probe(fn () => [$prompt->name(), $prompt->title(), $prompt->description(), $prompt->arguments()])) {
                continue;
            }

            if ($this->registry->isPromptRemoved($prompt->name())
                || ! $this->check(fn () => $prompt->isAvailableFor($actor))) {
                continue;
            }

            $prompts[$prompt->name()] = $prompt;
            $provenance['prompt:'.$prompt->name()] = $entry['extension'];
        }

        return new ResolvedCapabilities($tools, $resources, $templates, $prompts, $provenance, $readOnly);
    }

    /**
     * @param array<string, Tool> $tools
     * @param array<string, ?string> $provenance
     * @param array<string, bool> $readOnly
     */
    protected function addTool(array &$tools, array &$provenance, array &$readOnly, Tool $tool, ?string $extension, User $actor): void
    {
        $name = $this->failures->attempt(fn () => $tool->name());

        if (! is_string($name) || $this->registry->isToolRemoved($name)) {
            return;
        }

        if ($tool instanceof GenericTool) {
            $tool->forActor($actor);
        }

        if (! $this->check(fn () => $tool->isAvailableFor($actor))) {
            return;
        }

        $schema = $this->failures->attempt(fn () => $tool->inputSchema());

        if (! is_array($schema)) {
            return;
        }

        // A schema extender that throws is dropped with its handler, which
        // expects the argument it adds; the tool itself stays.
        foreach ($this->registry->toolExtensions($name) as $hooks) {
            if ($hooks['schema']) {
                $extended = $this->failures->attempt(fn () => ($hooks['schema'])($schema));

                if (! is_array($extended)) {
                    continue;
                }

                $schema = $extended;
                $tool->extendSchema($hooks['schema']);
            }

            if ($hooks['handler']) {
                $tool->extendHandler($hooks['handler']);
            }
        }

        $flags = $this->failures->attempt(function () use ($tool) {
            $tool->title();
            $tool->description();
            $tool->resolveInputSchema();
            $tool->outputSchema();
            $tool->destructive();
            $tool->idempotent();
            $tool->openWorld();

            return ['readOnly' => $tool->readOnly()];
        });

        if (! is_array($flags)) {
            return;
        }

        // A later registration of the same name replaces the earlier one.
        $tools[$name] = $tool;
        $provenance['tool:'.$name] = $extension;
        $readOnly[$name] = $flags['readOnly'];
    }

    /**
     * Reads a capability's metadata once; false (reported) when it throws.
     *
     * @param callable(): mixed $read
     */
    protected function probe(callable $read): bool
    {
        return $this->failures->attempt(function () use ($read) {
            $read();

            return true;
        }, false);
    }

    /**
     * @param callable(): bool $check
     */
    protected function check(callable $check): bool
    {
        return $this->failures->attempt($check, false);
    }
}
