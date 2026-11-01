<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

/**
 * A decoded JSON:API document returned by {@see ApiClient}.
 */
class ApiResult
{
    public const int MAX_STRING_LENGTH = 4000;
    public const int MAX_PAYLOAD_LENGTH = 100_000;

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed> $query the query the document was fetched with
     */
    public function __construct(
        protected array $document,
        protected array $query = [],
    ) {
    }

    /**
     * The raw document.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->document;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return (array) ($this->document['meta'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        return (array) ($this->document['links'] ?? []);
    }

    /**
     * Whether the primary data is a list of resources.
     */
    public function isList(): bool
    {
        $data = $this->document['data'] ?? null;

        return is_array($data) && array_is_list($data);
    }

    /**
     * The compact shape used in tool output: flat attributes, relationships as
     * identifiers, `*Html` attributes as plain text, long strings truncated and
     * the whole payload capped (lists are cut, with a `next` offset hint).
     *
     * @return array<string, mixed>
     */
    public function simplify(): array
    {
        $data = $this->document['data'] ?? null;

        $payload = [
            'data' => match (true) {
                $this->isList() => array_map(static::simplifyResource(...), $data),
                is_array($data) => static::simplifyResource($data),
                default => null,
            },
        ];

        $included = [];
        foreach ((array) ($this->document['included'] ?? []) as $resource) {
            if (is_array($resource) && isset($resource['type'], $resource['id'])) {
                $included[$resource['type'].':'.$resource['id']] ??= static::simplifyResource($resource);
            }
        }

        if ($included) {
            $payload['included'] = array_values($included);
        }

        $meta = $this->meta();
        if ($meta) {
            $payload['meta'] = $meta;
        }

        return static::fit($payload, (int) ($this->query['page']['offset'] ?? 0));
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    public static function simplifyResource(array $resource): array
    {
        $simple = [
            'type' => $resource['type'] ?? null,
            'id' => $resource['id'] ?? null,
        ];

        $simple += static::normaliseAttributes((array) ($resource['attributes'] ?? []));

        $relationships = [];
        foreach ((array) ($resource['relationships'] ?? []) as $name => $relationship) {
            if (! is_array($relationship) || ! array_key_exists('data', $relationship)) {
                continue;
            }

            $linkage = $relationship['data'];

            $relationships[$name] = match (true) {
                $linkage === null => null,
                is_array($linkage) && array_is_list($linkage) => array_map(
                    fn ($identifier) => ['type' => $identifier['type'] ?? null, 'id' => $identifier['id'] ?? null],
                    $linkage
                ),
                is_array($linkage) => ['type' => $linkage['type'] ?? null, 'id' => $linkage['id'] ?? null],
                default => null,
            };
        }

        if ($relationships) {
            $simple['relationships'] = $relationships;
        }

        return $simple;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function normaliseAttributes(array $attributes): array
    {
        $normalised = [];

        foreach ($attributes as $name => $value) {
            if (is_string($value) && str_ends_with((string) $name, 'Html') && strlen((string) $name) > 4) {
                $plain = substr((string) $name, 0, -4);

                if (! array_key_exists($plain, $attributes)) {
                    $normalised[$plain] = static::truncate(HtmlToText::convert($value));
                    continue;
                }
            }

            $normalised[$name] = is_string($value) ? static::truncate($value) : $value;
        }

        return $normalised;
    }

    public static function truncate(string $value, int $limit = self::MAX_STRING_LENGTH): string
    {
        $length = mb_strlen($value);

        if ($length <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit).'… [truncated, '.($length - $limit).' more characters]';
    }

    /**
     * Cap a tool payload. A list under `$listKey` is cut from the end, and
     * `meta.truncated` plus `meta.next.offset` tell the model where to resume.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function fit(array $payload, int $offset = 0, int $limit = self::MAX_PAYLOAD_LENGTH, string $listKey = 'data'): array
    {
        if (static::length($payload) <= $limit) {
            return $payload;
        }

        $data = $payload[$listKey] ?? null;

        if (is_array($data) && array_is_list($data)) {
            while ($data && static::length($payload) > $limit) {
                array_pop($data);
                $payload[$listKey] = $data;
                $payload = static::pruneIncluded($payload, $listKey);
            }

            $payload['meta'] = [
                ...(array) ($payload['meta'] ?? []),
                'truncated' => true,
                'next' => ['offset' => $offset + count($data)],
            ];
        } else {
            $payload['meta'] = [...(array) ($payload['meta'] ?? []), 'truncated' => true];
        }

        if (static::length($payload) > $limit) {
            unset($payload['included']);
        }

        return $payload;
    }

    /**
     * Keep only the included resources something left in the payload refers to.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected static function pruneIncluded(array $payload, string $listKey = 'data'): array
    {
        if (empty($payload['included'])) {
            return $payload;
        }

        $referenced = [];
        $collect = function (array $resource) use (&$referenced) {
            foreach ((array) ($resource['relationships'] ?? []) as $linkage) {
                foreach (is_array($linkage) && array_is_list($linkage) ? $linkage : [$linkage] as $identifier) {
                    if (is_array($identifier) && isset($identifier['type'], $identifier['id'])) {
                        $referenced[$identifier['type'].':'.$identifier['id']] = true;
                    }
                }
            }
        };

        foreach ((array) $payload[$listKey] as $resource) {
            $collect((array) $resource);
        }

        // One level of nesting covers the includes core tools request (post.user, discussion.user).
        foreach ($payload['included'] as $resource) {
            if (isset($referenced[$resource['type'].':'.$resource['id']])) {
                $collect($resource);
            }
        }

        $payload['included'] = array_values(array_filter(
            $payload['included'],
            fn (array $resource) => isset($referenced[$resource['type'].':'.$resource['id']])
        ));

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected static function length(array $payload): int
    {
        return mb_strlen((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
