<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Support;

use Flarum\Mcp\Api\ApiResult;

/**
 * Read helpers over a raw JSON:API document, for the curated tools.
 *
 * @internal
 */
class Document
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $included = [];

    /**
     * @param array<string, mixed> $document
     */
    public function __construct(protected array $document)
    {
        foreach ((array) ($document['included'] ?? []) as $resource) {
            if (is_array($resource) && isset($resource['type'], $resource['id'])) {
                $this->included[$resource['type'].':'.$resource['id']] = $resource;
            }
        }
    }

    public static function from(ApiResult $result): self
    {
        return new self($result->data());
    }

    /**
     * @return array<string, mixed>
     */
    public function one(): array
    {
        $data = $this->document['data'] ?? [];

        return is_array($data) && ! array_is_list($data) ? $data : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function many(): array
    {
        $data = $this->document['data'] ?? [];

        return is_array($data) && array_is_list($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return (array) ($this->document['meta'] ?? []);
    }

    /**
     * @param array<string, mixed> $resource
     */
    public static function attribute(array $resource, string $name): mixed
    {
        return $resource['attributes'][$name] ?? null;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array{type: string, id: string}|null
     */
    public static function related(array $resource, string $relationship): ?array
    {
        $linkage = $resource['relationships'][$relationship]['data'] ?? null;

        return is_array($linkage) && isset($linkage['type'], $linkage['id'])
            ? ['type' => (string) $linkage['type'], 'id' => (string) $linkage['id']]
            : null;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>|null
     */
    public function includedFor(array $resource, string $relationship): ?array
    {
        $identifier = static::related($resource, $relationship);

        return $identifier ? ($this->included[$identifier['type'].':'.$identifier['id']] ?? null) : null;
    }

    /**
     * @param array<string, mixed> $resource
     */
    public function username(array $resource, string $relationship = 'user'): ?string
    {
        $user = $this->includedFor($resource, $relationship);

        return $user ? (string) static::attribute($user, 'username') : null;
    }
}
