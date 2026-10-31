<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Api\Resource\AbstractResource;
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Exception\ToolError;
use Flarum\User\Guest;
use Flarum\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tobyz\JsonApiServer\Schema\Field\Relationship;

/**
 * A tool over one CRUD operation of every exposed JSON:API type. Its `type`
 * enum lists only what the actor is offered, so the resolver hands it the
 * actor before the schema is read.
 */
abstract class GenericTool extends Tool
{
    protected User $actor;

    public function __construct(
        protected ResourceExposure $exposure,
        protected TranslatorInterface $translator,
    ) {
        $this->actor = new Guest();
    }

    /**
     * The JSON:API endpoint name this tool calls.
     */
    abstract protected function operation(): string;

    /**
     * @internal called by the resolver for the request's actor
     */
    public function forActor(User $actor): void
    {
        $this->actor = $actor;
    }

    public function isAvailableFor(User $actor): bool
    {
        return $this->types() !== [];
    }

    /**
     * @return list<string>
     */
    protected function types(): array
    {
        return $this->exposure->typesWith($this->operation(), $this->actor);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeSchema(): array
    {
        return [
            'type' => 'string',
            'enum' => $this->types(),
            'description' => 'The JSON:API resource type. describe_api lists them with their fields.',
        ];
    }

    protected function exposedResource(string $type, McpContext $context): AbstractResource
    {
        $resource = $this->exposure->resource($type);

        if (! $resource || ! $this->exposure->endpoint($type, $this->operation(), $context->actor)) {
            throw new ToolError($this->translator->trans('flarum-mcp.api.not_exposed', [
                'operation' => $this->operation(),
                'type' => $type,
            ]));
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function query(array $arguments): array
    {
        $query = [];

        if (! empty($arguments['include'])) {
            $query['include'] = implode(',', array_map('strval', (array) $arguments['include']));
        }

        return $query;
    }

    /**
     * Turns `{name: id | [ids] | {type, id} | [{type, id}] | null}` into
     * JSON:API relationship objects. A bare ID takes the relationship's type
     * when it has exactly one.
     *
     * @param array<string, mixed> $relationships
     * @return array<string, array{data: mixed}>
     */
    protected function relationships(AbstractResource $resource, array $relationships): array
    {
        $fields = [];
        foreach ($resource->resolveFields() as $field) {
            if ($field instanceof Relationship) {
                $fields[$field->name] = $field;
            }
        }

        $document = [];

        foreach ($relationships as $name => $value) {
            $field = $fields[$name] ?? null;

            if (! $field) {
                throw new ToolError("`$name`: \"{$resource->type()}\" has no such relationship. describe_api lists them.");
            }

            $types = array_values($field->collections ?? []);
            $identifier = function (mixed $item) use ($name, $types): array {
                if (is_array($item) && isset($item['type'], $item['id'])) {
                    return ['type' => (string) $item['type'], 'id' => (string) $item['id']];
                }

                if ((is_string($item) || is_int($item)) && count($types) === 1) {
                    return ['type' => $types[0], 'id' => (string) $item];
                }

                throw new ToolError("`$name`: give {\"type\": ..., \"id\": ...}, as this relationship can hold ".implode(' or ', $types ?: ['several types']).'.');
            };

            $document[$name] = ['data' => match (true) {
                $value === null => null,
                is_array($value) && array_is_list($value) => array_map($identifier, $value),
                default => $identifier($value),
            }];
        }

        return $document;
    }
}
