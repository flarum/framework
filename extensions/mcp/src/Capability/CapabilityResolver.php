<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Mcp\Tool\Generic\EndpointTool;
use Flarum\Mcp\Tool\Generic\GenericTool;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Builds the capability instances an actor sees. Runs once per MCP request;
 * a capability that fails to build is reported and left out, so one broken
 * extension cannot take the endpoint down.
 */
class CapabilityResolver
{
    public function __construct(
        protected Container $container,
        protected CapabilityRegistry $registry,
    ) {
    }

    public function resolve(User $actor): ResolvedCapabilities
    {
        $provenance = [];

        $tools = [];
        foreach ($this->registry->tools() as $entry) {
            $tool = $this->build(fn () => $this->container->make($entry['class']));

            if ($tool instanceof Tool) {
                $this->addTool($tools, $provenance, $tool, $entry['extension'], $actor);
            }
        }

        foreach ($this->registry->endpointTools() as $entry) {
            $tool = $this->build(fn () => $this->container->make(EndpointTool::class, [
                'type' => $entry['type'],
                'endpoint' => $entry['endpoint'],
                'toolName' => $entry['name'],
                'toolTitle' => $entry['title'],
                'toolDescription' => $entry['description'],
                'isReadOnly' => $entry['readOnly'],
            ]));

            if ($tool instanceof Tool) {
                $this->addTool($tools, $provenance, $tool, $entry['extension'], $actor);
            }
        }

        $resources = [];
        foreach ($this->registry->resources() as $entry) {
            $resource = $this->build(fn () => $this->container->make($entry['class']));

            if (! $resource instanceof Resource
                || $this->registry->isResourceRemoved($resource->name())
                || $this->registry->isResourceRemoved($resource->uri())
                || ! $this->check(fn () => $resource->isAvailableFor($actor))) {
                continue;
            }

            $resources[$resource->uri()] = $resource;
            $provenance['resource:'.$resource->uri()] = $entry['extension'];
        }

        $templates = [];
        foreach ($this->registry->resourceTemplates() as $entry) {
            $template = $this->build(fn () => $this->container->make($entry['class']));

            if (! $template instanceof ResourceTemplate
                || $this->registry->isResourceRemoved($template->name())
                || $this->registry->isResourceRemoved($template->uriTemplate())
                || ! $this->check(fn () => $template->isAvailableFor($actor))) {
                continue;
            }

            $templates[$template->uriTemplate()] = $template;
            $provenance['template:'.$template->uriTemplate()] = $entry['extension'];
        }

        $prompts = [];
        foreach ($this->registry->prompts() as $entry) {
            $prompt = $this->build(fn () => $this->container->make($entry['class']));

            if (! $prompt instanceof Prompt
                || $this->registry->isPromptRemoved($prompt->name())
                || ! $this->check(fn () => $prompt->isAvailableFor($actor))) {
                continue;
            }

            $prompts[$prompt->name()] = $prompt;
            $provenance['prompt:'.$prompt->name()] = $entry['extension'];
        }

        return new ResolvedCapabilities($tools, $resources, $templates, $prompts, $provenance);
    }

    /**
     * @param array<string, Tool> $tools
     * @param array<string, ?string> $provenance
     */
    protected function addTool(array &$tools, array &$provenance, Tool $tool, ?string $extension, User $actor): void
    {
        $name = $tool->name();

        if ($this->registry->isToolRemoved($name)) {
            return;
        }

        if ($tool instanceof GenericTool) {
            $tool->forActor($actor);
        }

        if (! $this->check(fn () => $tool->isAvailableFor($actor))) {
            return;
        }

        foreach ($this->registry->toolExtensions($name) as $extensionHooks) {
            if ($extensionHooks['schema']) {
                $tool->extendSchema($extensionHooks['schema']);
            }

            if ($extensionHooks['handler']) {
                $tool->extendHandler($extensionHooks['handler']);
            }
        }

        // A later registration of the same name replaces the earlier one.
        $tools[$name] = $tool;
        $provenance['tool:'.$name] = $extension;
    }

    protected function build(callable $factory): mixed
    {
        try {
            return $factory();
        } catch (Throwable $e) {
            $this->report($e);

            return null;
        }
    }

    /**
     * @param callable(): bool $check
     */
    protected function check(callable $check): bool
    {
        try {
            return $check();
        } catch (Throwable $e) {
            $this->report($e);

            return false;
        }
    }

    protected function report(Throwable $e): void
    {
        foreach ($this->container->tagged(Reporter::class) as $reporter) {
            /** @var Reporter $reporter */
            $reporter->report($e);
        }
    }
}
