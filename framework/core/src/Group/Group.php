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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name_singular
 * @property string $name_plural
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_hidden
 * @property \Illuminate\Database\Eloquent\Collection $users
 * @property \Illuminate\Database\Eloquent\Collection $permissions
 */
class Group extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    const ADMINISTRATOR_ID = 1;
    const GUEST_ID = 2;
    const MEMBER_ID = 3;
    const MODERATOR_ID = 4;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    public static function boot()
    {
        parent::boot();

        static::deleted(function (self $group) {
            $group->raise(new Deleted($group));
        });
    }

    public static function build(?string $nameSingular, ?string $namePlural, ?string $color = null, ?string $icon = null, bool $isHidden = false): static
    {
        $group = new static;

        $group->name_singular = $nameSingular;
        $group->name_plural = $namePlural;
        $group->color = $color;
        $group->icon = $icon;
        $group->is_hidden = $isHidden;

        $group->raise(new Created($group));

        return $group;
    }

    public function rename(string $nameSingular, string $namePlural): static
    {
        $this->name_singular = $nameSingular;
        $this->name_plural = $namePlural;

        $this->raise(new Renamed($this));

        return $this;
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->id == self::ADMINISTRATOR_ID) {
            return true;
        }

        return $this->permissions->contains('permission', $permission);
    }
}
