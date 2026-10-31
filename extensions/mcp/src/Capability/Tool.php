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
 * An MCP tool. Subclasses are resolved from the container once per MCP
 * request, so constructor injection works and instance state never outlives
 * the request.
 */
abstract class Tool
{
    /**
     * @var list<callable(array<string, mixed>): array<string, mixed>>
     */
    protected array $schemaExtenders = [];

    /**
     * @var list<callable(array<string, mixed>, array<string, mixed>, McpContext): array<string, mixed>>
     */
    protected array $handlerExtenders = [];

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $resolvedInputSchema = null;

    /**
     * snake_case, at most 64 characters of [a-z0-9_].
     */
    abstract public function name(): string;

    abstract public function title(): string;

    abstract public function description(): string;

    /**
     * A JSON Schema object describing the arguments.
     *
     * @return array<string, mixed>
     */
    abstract public function inputSchema(): array;

    /**
     * An object schema here makes the result's array value travel as
     * `structuredContent` as well as JSON text.
     *
     * @return array<string, mixed>|null
     */
    public function outputSchema(): ?array
    {
        return null;
    }

    /**
     * Whether the tool only reads. False unless a tool says otherwise, so a tool
     * that forgets to declare it is challenged for write access. This is only
     * the advertised behaviour: {@see \Flarum\Mcp\Api\ApiClient} refuses every
     * write without the `mcp:write` scope whatever the tool declares.
     */
    public function readOnly(): bool
    {
        return false;
    }

    /**
     * Ignored when the tool is read-only.
     */
    public function destructive(): bool
    {
        return false;
    }

    public function idempotent(): bool
    {
        return $this->readOnly();
    }

    public function openWorld(): bool
    {
        return false;
    }

    /**
     * Whether the actor sees the tool at all. Keep it cheap: it runs for every
     * tool on every MCP request.
     */
    public function isAvailableFor(User $actor): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    abstract public function handle(array $arguments, McpContext $context): mixed;

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback receives the input schema, returns the new one
     */
    public function extendSchema(callable $callback): static
    {
        $this->schemaExtenders[] = $callback;
        $this->resolvedInputSchema = null;

        return $this;
    }

    /**
     * Only tools that call {@see applyHandlerExtenders()} honour these.
     *
     * @param callable(array<string, mixed>, array<string, mixed>, McpContext): array<string, mixed> $callback receives the arguments, the API query or body the tool built, and the context; returns the new query or body
     */
    public function extendHandler(callable $callback): static
    {
        $this->handlerExtenders[] = $callback;

        return $this;
    }

    /**
     * The input schema with every {@see extendSchema()} callback applied.
     * Computed once per tool instance, that is once per request.
     *
     * @return array<string, mixed>
     */
    public function resolveInputSchema(): array
    {
        if ($this->resolvedInputSchema !== null) {
            return $this->resolvedInputSchema;
        }

        $schema = $this->inputSchema();

        foreach ($this->schemaExtenders as $extender) {
            $schema = $extender($schema);
        }

        return $this->resolvedInputSchema = $schema;
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $apiQuery
     * @return array<string, mixed>
     */
    protected function applyHandlerExtenders(array $arguments, array $apiQuery, McpContext $context): array
    {
        foreach ($this->handlerExtenders as $extender) {
            $apiQuery = $extender($arguments, $apiQuery, $context);
        }

        return $apiQuery;
    }
}
