<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Groups;

use Flarum\Core\Model;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\ValidatesBeforeSave;
use Flarum\Events\GroupWasDeleted;
use Flarum\Events\GroupWasCreated;
use Flarum\Events\GroupWasRenamed;

/**
 * @todo document database columns with @property
 */
class Group extends Model
{
    use ValidatesBeforeSave;
    use EventGenerator;
    use Locked;
    use VisibleScope;

    /**
     * {@inheritdoc}
     */
    protected $table = 'groups';

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    protected $rules = [
        'name_singular' => 'required',
        'name_plural'   => 'required'
    ];

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
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($group) {
            $group->raise(new GroupWasDeleted($group));

            $group->permissions()->delete();
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
        $group->name_plural   = $namePlural;
        $group->color         = $color;
        $group->icon          = $icon;

        $group->raise(new GroupWasCreated($group));

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
        $this->name_plural   = $namePlural;

        return $this;
    }

    /**
     * Define the relationship with the group's users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('Flarum\Core\Users\User', 'users_groups');
    }

    /**
     * Define the relationship with the group's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany('Flarum\Core\Groups\Permission');
    }
}
