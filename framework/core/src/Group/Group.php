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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name_singular
 * @property string $name_plural
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_hidden
 * @property-read \Illuminate\Database\Eloquent\Collection $users
 * @property-read \Illuminate\Database\Eloquent\Collection $permissions
 */
class Group extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasFactory;

    public const ADMINISTRATOR_ID = 1;
    public const GUEST_ID = 2;
    public const MEMBER_ID = 3;
    public const MODERATOR_ID = 4;

    protected $casts = [
        'id' => 'integer',
        'is_hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleted(function (self $group) {
            $group->raise(new Deleted($group));
        });

        static::creating(function (self $group) {
            $group->raise(new Created($group));
        });
    }

    public function rename(?string $nameSingular, ?string $namePlural): static
    {
        if ($nameSingular !== null) {
            $this->name_singular = $nameSingular;
        }

        if ($namePlural !== null) {
            $this->name_plural = $namePlural;
        }

        if ($this->isDirty(['name_singular', 'name_plural'])) {
            $this->raise(new Renamed($this));
        }

        return $this;
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

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
