<?php

namespace Flarum\Api\Resource\Concerns;

trait Extendable
{
    private static array $endpointModifiers = [];
    private static array $fieldModifiers = [];
    private static array $sortModifiers = [];

    private ?array $cachedEndpoints = null;
    private ?array $cachedFields = null;
    private ?array $cachedSorts = null;

    public static function mutateEndpoints(callable $modifier): void
    {
        static::$endpointModifiers[static::class][] = $modifier;
    }

    public static function mutateFields(callable $modifier): void
    {
        static::$fieldModifiers[static::class][] = $modifier;
    }

    public static function mutateSorts(callable $modifier): void
    {
        static::$sortModifiers[static::class][] = $modifier;
    }

    public function resolveEndpoints(bool $earlyResolution = false): array
    {
        if (! is_null($this->cachedEndpoints) && ! $earlyResolution) {
            return $this->cachedEndpoints;
        }

        $endpoints = $this->endpoints();

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$endpointModifiers[$class])) {
                foreach (static::$endpointModifiers[$class] as $modifier) {
                    $endpoints = $modifier($endpoints, $this);
                }
            }
        }

        return $this->cachedEndpoints = $endpoints;
    }

    public function resolveFields(): array
    {
        if (! is_null($this->cachedFields)) {
            return $this->cachedFields;
        }

        $fields = $this->fields();

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$fieldModifiers[$class])) {
                foreach (static::$fieldModifiers[$class] as $modifier) {
                    $fields = $modifier($fields, $this);
                }
            }
        }

        return $this->cachedFields = $fields;
    }

    public function resolveSorts(): array
    {
        if (! is_null($this->cachedSorts)) {
            return $this->cachedSorts;
        }

        $sorts = $this->sorts();

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$sortModifiers[$class])) {
                foreach (static::$sortModifiers[$class] as $modifier) {
                    $sorts = $modifier($sorts, $this);
                }
            }
        }

        return $this->cachedSorts = $sorts;
    }
}
