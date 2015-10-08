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

use Flarum\Core\Discussion;
use Flarum\Core\Permission;
use Flarum\Core\Support\ScopeVisibilityTrait;
use Flarum\Database\AbstractModel;

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
    protected $dates = ['last_time'];

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($tag) {
            $tag->discussions()->detach();

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

        $tag->name        = $name;
        $tag->slug        = $slug;
        $tag->description = $description;
        $tag->color       = $color;
        $tag->is_hidden   = (bool) $isHidden;

        return $tag;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Flarum\Tags\Tag', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastDiscussion()
    {
        return $this->belongsTo('Flarum\Core\Discussion', 'last_discussion_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function discussions()
    {
        return $this->belongsToMany('Flarum\Core\Discussion', 'discussions_tags');
    }

    /**
     * Refresh a tag's last discussion details.
     *
     * @return $this
     */
    public function refreshLastDiscussion()
    {
        if ($lastDiscussion = $this->discussions()->latest('last_time')->first()) {
            $this->setLastDiscussion($lastDiscussion);
        }

        return $this;
    }

    /**
     * Set the tag's last discussion details.
     *
     * @param Discussion $discussion
     * @return $this
     */
    public function setLastDiscussion(Discussion $discussion)
    {
        $this->last_time          = $discussion->last_time;
        $this->last_discussion_id = $discussion->id;

        return $this;
    }

    /**
     * @param $user
     * @param $permission
     * @return array
     */
    public static function getIdsWhereCan($user, $permission)
    {
        static $tags;

        if (! $tags) {
            $tags = static::all();
        }

        $ids = [];
        $hasGlobalPermission = $user->hasPermission($permission === 'view' ? 'forum.view' : $permission);

        foreach ($tags as $tag) {
            if (($hasGlobalPermission && ! $tag->is_restricted) || $user->hasPermission('tag' . $tag->id . '.' . $permission)) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }

    /**
     * @param $user
     * @param $permission
     * @return array
     */
    public static function getIdsWhereCannot($user, $permission)
    {
        static $tags;

        if (! $tags) {
            $tags = static::all();
        }

        $ids = [];
        $hasGlobalPermission = $user->hasPermission($permission === 'view' ? 'forum.view' : $permission);

        foreach ($tags as $tag) {
            if (($tag->is_restricted || ! $hasGlobalPermission) && ! $user->hasPermission('tag' . $tag->id . '.' . $permission)) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }
}
