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
use Illuminate\Database\Eloquent\Collection;

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
 *
 * @property TagState $state
 * @property Tag|null $parent
 * @property-read Collection<Tag> $children
 * @property-read Collection<Discussion> $discussions
 * @property Discussion|null $lastPostedDiscussion
 * @property User|null $lastPostedUser
 */
class Tag extends AbstractModel
{
    use ScopeVisibilityTrait;

    protected $table = 'tags';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_posted_at', 'created_at', 'updated_at'];

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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
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

    protected static function buildPermissionSubquery($base, $isAdmin, $hasGlobalPermission, $tagIdsWithPermission)
    {
        $base
            ->from('tags as perm_tags')
            ->select('perm_tags.id');

        // This needs to be a special case, as `tagIdsWithPermissions`
        // won't include admin perms (which are all perms by default).
        if ($isAdmin) {
            return;
        }

        $base->where(function ($query) use ($tagIdsWithPermission) {
            $query
                ->where('perm_tags.is_restricted', true)
                ->whereIn('perm_tags.id', $tagIdsWithPermission);
        });

        if ($hasGlobalPermission) {
            $base->orWhere('perm_tags.is_restricted', false);
        }
    }

    public function scopeWhereHasPermission(Builder $query, User $user, string $currPermission): Builder
    {
        $hasGlobalPermission = $user->hasPermission($currPermission);
        $isAdmin = $user->isAdmin();
        $allPermissions = $user->getPermissions();

        $tagIdsWithPermission = collect($allPermissions)
            ->filter(function ($permission) use ($currPermission) {
                return substr($permission, 0, 3) === 'tag' && strpos($permission, $currPermission) !== false;
            })
            ->map(function ($permission) {
                $scopeFragment = explode('.', $permission, 2)[0];

                return substr($scopeFragment, 3);
            })
            ->values();

        return $query
            ->where(function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                $query
                    ->whereIn('tags.id', function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                        static::buildPermissionSubquery($query, $isAdmin, $hasGlobalPermission, $tagIdsWithPermission);
                    })
                    ->where(function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                        $query
                            ->whereIn('tags.parent_id', function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                                static::buildPermissionSubquery($query, $isAdmin, $hasGlobalPermission, $tagIdsWithPermission);
                            })
                            ->orWhere('tags.parent_id', null);
                    });
            });
    }
}
