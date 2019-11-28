<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Group\Event\Created;
use Flarum\Group\Event\Deleted;
use Flarum\Group\Event\Renamed;
use Flarum\User\User;

/**
 * @property int $id
 * @property string $name_singular
 * @property string $name_plural
 * @property string|null $color
 * @property string|null $icon
 * @property \Illuminate\Database\Eloquent\Collection $users
 * @property \Illuminate\Database\Eloquent\Collection $permissions
 */
class Group extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * The ID of the administrator group.
     */
    const ADMINISTRATOR_ID = 1;

    /**
     * The ID of the guest group.
     */
    const GUEST_ID = 2;

    /**
     * The ID of the member group.
     */
    const MEMBER_ID = 3;

    /**
     * The ID of the mod group.
     */
    const MODERATOR_ID = 4;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function (self $group) {
            $group->raise(new Deleted($group));
        });
    }

    /**
     * Create a new group.
     *
     * @param string $nameSingular
     * @param string $namePlural
     * @param string $color
     * @param string $icon
     * @return static
     */
    public static function build($nameSingular, $namePlural, $color, $icon)
    {
        $group = new static;

        $group->name_singular = $nameSingular;
        $group->name_plural = $namePlural;
        $group->color = $color;
        $group->icon = $icon;

        $group->raise(new Created($group));

        return $group;
    }

    /**
     * Rename the group.
     *
     * @param string $nameSingular
     * @param string $namePlural
     * @return $this
     */
    public function rename($nameSingular, $namePlural)
    {
        $this->name_singular = $nameSingular;
        $this->name_plural = $namePlural;

        $this->raise(new Renamed($this));

        return $this;
    }

    /**
     * Define the relationship with the group's users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Define the relationship with the group's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Check whether the group has a certain permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->id == self::ADMINISTRATOR_ID) {
            return true;
        }

        return $this->permissions->contains('permission', $permission);
    }
}
