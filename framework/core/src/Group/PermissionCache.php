<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

/**
 * Caches the permission lists of group-ID sets for the lifetime of the
 * application instance.
 *
 * The permissions a user has are fully determined by the set of group IDs
 * they belong to, so users sharing a group set can share one `group_permission`
 * load. Without this, any code that checks an ability against each serialized
 * user — extension serializer flags, policies, visibility scopers — issues one
 * identical query per user instance.
 *
 * The cache is a container singleton, so its lifetime is one request under
 * PHP-FPM. Entries are invalidated when permissions change: per affected group
 * for model writes, and wholesale for the admin permission endpoint (which
 * writes through the query builder and bypasses model events). Code that
 * modifies the `group_permission` table directly should call `flush()`.
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
     * Forget every cached set that contains the given group, e.g. because the
     * group's permissions changed or the group was deleted.
     */
    public function forgetGroup(int $groupId): void
    {
        foreach (array_keys($this->permissions) as $key) {
            if (in_array((string) $groupId, explode(',', $key), true)) {
                unset($this->permissions[$key]);
            }
        }
    }

    public function flush(): void
    {
        $this->permissions = [];
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
