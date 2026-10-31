<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Api\Schema;
use Flarum\Extension\ExtensionManager;
use Flarum\Mcp\Capability\CapabilityResolver;
use Flarum\Mcp\Capability\ResolvedCapabilities;
use Flarum\Mcp\Overview\ExtensionOwnership;
use Tobyz\JsonApiServer\Context;
use Flarum\Api\Resource\Contracts\Listable;

/**
 * What the MCP server offers, for the admin page: every tool, resource,
 * resource template and prompt, as resolved for the admin asking, with the
 * extension that registered it. Index only, admins only.
 *
 * @extends AbstractResource<object>
 */
class CapabilityResource extends AbstractResource implements Listable
{
    public function __construct(
        protected CapabilityResolver $resolver,
        protected ExtensionManager $extensions,
    ) {
    }

    public function type(): string
    {
        return 'mcp-capabilities';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->admin(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('kind')
                ->description('tool, resource, template (a resource template) or prompt.'),
            Schema\Str::make('name')
                ->description('The tool or prompt name, or the resource URI (template).'),
            Schema\Str::make('title')
                ->nullable(),
            Schema\Str::make('description')
                ->nullable(),
            Schema\Str::make('extension')
                ->description('ID of the extension that registered it, or core.'),
            Schema\Str::make('extensionTitle'),
            Schema\Boolean::make('readOnly')
                ->nullable()
                ->description('For tools: whether it only reads. Null for other kinds.'),
            Schema\Boolean::make('destructive')
                ->nullable()
                ->description('For write tools: whether it deletes or overwrites.'),
        ];
    }

    public function query(Context $context): object
    {
        return new \stdClass();
    }

    /**
     * @return list<object>
     */
    public function results(object $query, Context $context): array
    {
        $capabilities = $this->resolver->resolve($context->getActor());
        $rows = [];

        foreach ($capabilities->tools as $name => $tool) {
            $rows[] = $this->row($capabilities, 'tool', 'tool:'.$name, $name, $tool->title(), $tool->description(), [
                'readOnly' => $tool->readOnly(),
                'destructive' => ! $tool->readOnly() && $tool->destructive(),
            ]);
        }

        foreach ($capabilities->resources as $uri => $resource) {
            $rows[] = $this->row($capabilities, 'resource', 'resource:'.$uri, $uri, $resource->title() ?? $resource->name(), $resource->description());
        }

        foreach ($capabilities->resourceTemplates as $uri => $template) {
            $rows[] = $this->row($capabilities, 'template', 'template:'.$uri, $uri, $template->title() ?? $template->name(), $template->description());
        }

        foreach ($capabilities->prompts as $name => $prompt) {
            $rows[] = $this->row($capabilities, 'prompt', 'prompt:'.$name, $name, $prompt->title(), $prompt->description());
        }

        return $rows;
    }

    /**
     * @param array{readOnly?: bool, destructive?: bool} $flags
     */
    protected function row(ResolvedCapabilities $capabilities, string $kind, string $key, string $name, ?string $title, ?string $description, array $flags = []): object
    {
        $extensionId = $capabilities->provenance[$key] ?? null;
        $extension = $extensionId ? $this->extensions->getExtension($extensionId) : null;

        return (object) [
            'id' => $key,
            'kind' => $kind,
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'extension' => $extensionId ?? ExtensionOwnership::CORE,
            'extensionTitle' => $extension?->getTitle() ?? 'Flarum',
            'readOnly' => $flags['readOnly'] ?? null,
            'destructive' => $flags['destructive'] ?? null,
        ];
    }
}
