<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

/**
 * Caches the permission lists of group-ID sets.
 *
 * The permissions a user has are fully determined by the set of group IDs
 * they belong to, so users sharing a group set can share one `group_permission`
 * load. Without this, any code that checks an ability against each serialized
 * user — extension serializer flags, policies, visibility scopers — issues one
 * identical query per user instance.
 *
 * The cache is a container singleton, so its entries last one request under
 * PHP-FPM; a queue worker flushes it as each job starts. Changes made by
 * another process never reach it, so other long-running processes must flush
 * it themselves. Within a process, entries are invalidated when permissions
 * change: per affected group for model writes, and wholesale for the admin
 * permission endpoint (which writes through the query builder and bypasses
 * model events). Code that modifies the `group_permission` table directly
 * should call `flush()`.
 *
 * Values derived from a group set's permissions, such as a policy verdict,
 * can be kept here with `remember()`, so that they share this lifecycle.
 */
class PermissionCache
{
    /**
     * Permission lists keyed by sorted group-ID set.
     *
     * @var array<string, string[]>
     */
    protected array $permissions = [];

    /**
     * Derived values keyed by sorted group-ID set, then by the caller's key.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $derived = [];

    /**
     * @param int[] $groupIds
     * @return string[]|null
     */
    public function get(array $groupIds): ?array
    {
        return $this->permissions[$this->key($groupIds)] ?? null;
    }

    /**
     * @param int[] $groupIds
     * @param string[] $permissions
     */
    public function put(array $groupIds, array $permissions): void
    {
        $this->permissions[$this->key($groupIds)] = $permissions;
    }

    /**
     * A value derived from the group set's permissions, worked out by the
     * callback the first time it is asked for and forgotten with the set.
     *
     * The key must name everything else the value depends on: two callers
     * with the same group set and key are given the same value.
     *
     * @template T
     * @param int[] $groupIds
     * @param callable(): T $callback
     * @return T
     */
    public function remember(array $groupIds, string $key, callable $callback): mixed
    {
        $set = $this->key($groupIds);

        if (! array_key_exists($key, $this->derived[$set] ?? [])) {
            $this->derived[$set][$key] = $callback();
        }

        return $this->derived[$set][$key];
    }

    /**
     * Forget every cached set that contains the given group, e.g. because the
     * group's permissions changed or the group was deleted.
     */
    public function forgetGroup(int $groupId): void
    {
        $this->permissions = $this->withoutGroup($this->permissions, $groupId);
        $this->derived = $this->withoutGroup($this->derived, $groupId);
    }

    public function flush(): void
    {
        $this->permissions = [];
        $this->derived = [];
    }

    /**
     * @template T of array
     * @param T $entries
     * @return T
     */
    protected function withoutGroup(array $entries, int $groupId): array
    {
        return array_filter(
            $entries,
            fn (int|string $key) => ! in_array((string) $groupId, explode(',', (string) $key), true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param int[] $groupIds
     */
    protected function key(array $groupIds): string
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));

        sort($groupIds);

        return implode(',', $groupIds);
    }
}
