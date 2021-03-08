<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Group\Permission;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $color
 * @property string $background_path
 * @property string $background_mode
 * @property int $position
 * @property int $parent_id
 * @property string $default_sort
 * @property bool $is_restricted
 * @property bool $is_hidden
 * @property int $discussion_count
 * @property \Carbon\Carbon $last_posted_at
 * @property int $last_posted_discussion_id
 * @property int $last_posted_user_id
 * @property string $icon
 */
class Tag extends AbstractModel
{
    use ScopeVisibilityTrait;

    protected $table = 'tags';

    protected $dates = ['last_posted_at'];

    protected $casts = [
        'is_hidden' => 'bool',
        'is_restricted' => 'bool'
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function (self $tag) {
            if ($tag->wasUnrestricted()) {
                $tag->deletePermissions();
            }
        });

        static::deleted(function (self $tag) {
            $tag->deletePermissions();
        });
    }

    /**
     * Create a new tag.
     *
     * @param string $name
     * @param string $slug
     * @param string $description
     * @param string $color
     * @param string $icon
     * @param bool $isHidden
     * @return static
     */
    public static function build($name, $slug, $description, $color, $icon, $isHidden)
    {
        $tag = new static;

        $tag->name = $name;
        $tag->slug = $slug;
        $tag->description = $description;
        $tag->color = $color;
        $tag->icon = $icon;
        $tag->is_hidden = (bool) $isHidden;

        return $tag;
    }

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function lastPostedDiscussion()
    {
        return $this->belongsTo(Discussion::class, 'last_posted_discussion_id');
    }

    public function lastPostedUser()
    {
        return $this->belongsTo(User::class, 'last_posted_user_id');
    }

    public function discussions()
    {
        return $this->belongsToMany(Discussion::class);
    }

    public function refreshLastPostedDiscussion()
    {
        if ($lastPostedDiscussion = $this->discussions()->where('is_private', false)->whereNull('hidden_at')->latest('last_posted_at')->first()) {
            $this->setLastPostedDiscussion($lastPostedDiscussion);
        } else {
            $this->setLastPostedDiscussion(null);
        }

        return $this;
    }

    public function setLastPostedDiscussion(Discussion $discussion = null)
    {
        $this->last_posted_at = optional($discussion)->last_posted_at;
        $this->last_posted_discussion_id = optional($discussion)->id;
        $this->last_posted_user_id = optional($discussion)->last_posted_user_id;

        return $this;
    }

    /**
     * Define the relationship with the tag's state for a particular user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function state()
    {
        return $this->hasOne(TagState::class);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     *
     * @param User $user
     * @return TagState
     */
    public function stateFor(User $user)
    {
        $state = $this->state()->where('user_id', $user->id)->first();

        if (! $state) {
            $state = new TagState;
            $state->tag_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    /**
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeWithStateFor(Builder $query, User $user)
    {
        return $query->with([
            'state' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ]);
    }

    /**
     * Has this tag been unrestricted recently?
     *
     * @return bool
     */
    public function wasUnrestricted()
    {
        return ! $this->is_restricted && $this->wasChanged('is_restricted');
    }

    /**
     * Delete all permissions belonging to this tag.
     */
    public function deletePermissions()
    {
        Permission::where('permission', 'like', "tag{$this->id}.%")->delete();
    }

    protected static function getIdsWherePermission(User $user, string $permission, bool $condition = true, bool $includePrimary = true, bool $includeSecondary = true): array
    {
        static $tags;

        if (! $tags) {
            $tags = static::with('parent')->get();
        }

        $ids = [];
        $hasGlobalPermission = $user->hasPermission($permission);

        $canForTag = function (self $tag) use ($user, $permission, $hasGlobalPermission) {
            return ($hasGlobalPermission && ! $tag->is_restricted) || ($tag->is_restricted && $user->hasPermission('tag'.$tag->id.'.'.$permission));
        };

        foreach ($tags as $tag) {
            $can = $canForTag($tag);

            if ($can && $tag->parent) {
                $can = $canForTag($tag->parent);
            }

            $isPrimary = $tag->position !== null && ! $tag->parent;

            if ($can === $condition && ($includePrimary && $isPrimary || $includeSecondary && ! $isPrimary)) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }

    public static function getIdsWhereCan(User $user, string $permission, bool $includePrimary = true, bool $includeSecondary = true): array
    {
        return static::getIdsWherePermission($user, $permission, true, $includePrimary, $includeSecondary);
    }

    public static function getIdsWhereCannot(User $user, string $permission, bool $includePrimary = true, bool $includeSecondary = true): array
    {
        return static::getIdsWherePermission($user, $permission, false, $includePrimary, $includeSecondary);
    }
}
