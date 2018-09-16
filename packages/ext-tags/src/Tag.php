<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Group\Permission;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class Tag extends AbstractModel
{
    use ScopeVisibilityTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'tags';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['last_posted_at'];

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($tag) {
            Permission::where('permission', 'like', "tag{$tag->id}.%")->delete();
        });
    }

    /**
     * Create a new tag.
     *
     * @param string $name
     * @param string $slug
     * @param string $description
     * @param string $color
     * @param bool $isHidden
     * @return static
     */
    public static function build($name, $slug, $description, $color, $isHidden)
    {
        $tag = new static;

        $tag->name = $name;
        $tag->slug = $slug;
        $tag->description = $description;
        $tag->color = $color;
        $tag->is_hidden = (bool) $isHidden;

        return $tag;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPostedDiscussion()
    {
        return $this->belongsTo(Discussion::class, 'last_posted_discussion_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPostedUser()
    {
        return $this->belongsTo(User::class, 'last_posted_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function discussions()
    {
        return $this->belongsToMany(Discussion::class);
    }

    /**
     * Refresh a tag's last discussion details.
     *
     * @return $this
     */
    public function refreshLastPostedDiscussion()
    {
        if ($lastPostedDiscussion = $this->discussions()->latest('last_posted_at')->first()) {
            $this->setLastPostedDiscussion($lastPostedDiscussion);
        }

        return $this;
    }

    /**
     * Set the tag's last discussion details.
     *
     * @param Discussion $discussion
     * @return $this
     */
    public function setLastPostedDiscussion(Discussion $discussion)
    {
        $this->last_posted_at = $discussion->last_posted_at;
        $this->last_posted_discussion_id = $discussion->id;
        $this->last_posted_user_id = $discussion->last_posted_user_id;

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
     * @param User $user
     * @param string $permission
     * @param bool $condition
     * @return array
     */
    protected static function getIdsWherePermission(User $user, $permission, $condition = true)
    {
        static $tags;

        if (! $tags) {
            $tags = static::with('parent')->get();
        }

        $ids = [];
        $hasGlobalPermission = $user->hasPermission($permission);

        $canForTag = function (Tag $tag) use ($user, $permission, $hasGlobalPermission) {
            return ($hasGlobalPermission && ! $tag->is_restricted) || ($tag->is_restricted && $user->hasPermission('tag'.$tag->id.'.'.$permission));
        };

        foreach ($tags as $tag) {
            $can = $canForTag($tag);

            if ($can && $tag->parent) {
                $can = $canForTag($tag->parent);
            }

            if ($can === $condition) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }

    /**
     * @param User $user
     * @param string $permission
     * @return array
     */
    public static function getIdsWhereCan(User $user, $permission)
    {
        return static::getIdsWherePermission($user, $permission, true);
    }

    /**
     * @param User $user
     * @param string $permission
     * @return array
     */
    public static function getIdsWhereCannot(User $user, $permission)
    {
        return static::getIdsWherePermission($user, $permission, false);
    }
}
