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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $group_id
 * @property string $permission
 */
class Permission extends AbstractModel
{
    protected $table = 'group_permission';

    protected $casts = [
        'group_id' => 'integer',
        'created_at' => 'datetime'
    ];

    public $incrementing = false;

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
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
    public static function map(): array
    {
        $permissions = [];

        foreach (static::get() as $permission) {
            $permissions[$permission->permission][] = (string) $permission->group_id;
        }

        return $permissions;
    }
}
