<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $group_id
 * @property string $permission
 */
class Permission extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'group_permission';

    /**
     * Define the relationship with the group that this permission is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('group_id', $this->group_id)
              ->where('permission', $this->permission);

        return $query;
    }

    /**
     * Get a map of permissions to the group IDs that have them.
     *
     * @return array[]
     */
    public static function map()
    {
        $permissions = [];

        foreach (static::get() as $permission) {
            $permissions[$permission->permission][] = (string) $permission->group_id;
        }

        return $permissions;
    }
}
