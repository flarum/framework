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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $color
 * @property bool $is_primary
 * @property int $position
 * @property int $parent_id
 * @property string|null $default_sort
 * @property bool $is_restricted
 * @property bool $is_hidden
 * @property int $discussion_count
 * @property \Carbon\Carbon $last_posted_at
 * @property int $last_posted_discussion_id
 * @property int $last_posted_user_id
 * @property string $icon
 *
 * @property TagState|null $state
 * @property Tag|null $parent
 * @property-read Collection<int, Tag> $children
 * @property-read Collection<int, Discussion> $discussions
 * @property Discussion|null $lastPostedDiscussion
 * @property User|null $lastPostedUser
 */
class Tag extends AbstractModel
{
    use ScopeVisibilityTrait;
    use HasFactory;

    protected $table = 'tags';

    protected $casts = [
        'is_hidden' => 'bool',
        'is_restricted' => 'bool',
        'is_primary' => 'bool',
        'last_posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function (self $tag) {
            if ($tag->wasUnrestricted()) {
                $tag->deletePermissions();
            }
        });

        static::creating(function (self $tag) {
            if ($tag->is_primary) {
                $tag->position = static::query()
                    ->when($tag->parent_id, fn ($query) => $query->where('parent_id', $tag->parent_id))
                    ->where('is_primary', true)
                    ->max('position') + 1;
            }
        });

        static::deleted(function (self $tag) {
            $tag->deletePermissions();
        });
    }

    public static function build(?string $name, ?string $slug, ?string $description, ?string $color, ?string $icon, ?bool $isHidden): static
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lastPostedDiscussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'last_posted_discussion_id');
    }

    public function lastPostedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_posted_user_id');
    }

    /**
     * @return BelongsToMany<Discussion, $this>
     */
    public function discussions(): BelongsToMany
    {
        return $this->belongsToMany(Discussion::class);
    }

    public function refreshLastPostedDiscussion(): static
    {
        if ($lastPostedDiscussion = $this->discussions()->where('is_private', false)->whereNull('hidden_at')->latest('last_posted_at')->first()) {
            $this->setLastPostedDiscussion($lastPostedDiscussion);
        } else {
            $this->setLastPostedDiscussion(null);
        }

        return $this;
    }

    public function setLastPostedDiscussion(?Discussion $discussion = null): static
    {
        $this->last_posted_at = optional($discussion)->last_posted_at;
        $this->last_posted_discussion_id = optional($discussion)->id;
        $this->last_posted_user_id = optional($discussion)->last_posted_user_id;

        return $this;
    }

    /**
     * @return HasOne<TagState, $this>
     */
    public function state(): HasOne
    {
        return $this->hasOne(TagState::class);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     */
    public function stateFor(User $user): TagState
    {
        // Use the loaded state if the relation is loaded, and either:
        // 1. The state is null, or
        // 2. The state belongs to the given user.
        // This ensures that if a non-null state is loaded, it belongs to the correct user.
        // If these conditions are not met, we query the database for the user's state.
        if ($this->relationLoaded('state') && (! $this->state || $this->state->user_id === $user->id)) {
            $state = $this->state;
        } else {
            $state = $this->state()->where('user_id', $user->id)->first();
        }

        if (! $state) {
            $state = new TagState;
            $state->tag_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    public function scopeWithStateFor(Builder $query, User $user): Builder
    {
        return $query->with([
            'state' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ]);
    }

    /**
     * Has this tag been unrestricted recently?
     */
    public function wasUnrestricted(): bool
    {
        return ! $this->is_restricted && $this->wasChanged('is_restricted');
    }

    /**
     * Delete all permissions belonging to this tag.
     */
    public function deletePermissions(): void
    {
        Permission::where('permission', 'like', "tag{$this->id}.%")->delete();
    }

    /**
     * Build a subquery that selects the IDs of tags the user has the given
     * permission on. Used inline by scopeWhereHasPermission so the DB does
     * the filtering — avoids fetching every tag into PHP per call.
     */
    protected static function buildPermissionSubquery(QueryBuilder $base, bool $isAdmin, bool $hasGlobalPermission, iterable $tagIdsWithPermission): void
    {
        $base
            ->from('tags as perm_tags')
            ->select('perm_tags.id');

        // Admins have all permissions in all tags by default; no need to
        // narrow the subquery.
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

        $tagIdsWithPermission = collect($user->getPermissions())
            ->filter(fn (string $p) => str_starts_with($p, 'tag') && str_contains($p, $currPermission))
            ->map(fn (string $p) => (int) substr(explode('.', $p, 2)[0], 3))
            ->values();

        return $query
            ->where(function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                $query
                    ->whereIn('tags.id', function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                        static::buildPermissionSubquery($query, $isAdmin, $hasGlobalPermission, $tagIdsWithPermission);
                    })
                    ->where(
                        fn ($query) => $query
                            ->whereIn('tags.parent_id', function ($query) use ($isAdmin, $hasGlobalPermission, $tagIdsWithPermission) {
                                static::buildPermissionSubquery($query, $isAdmin, $hasGlobalPermission, $tagIdsWithPermission);
                            })
                            ->orWhereNull('tags.parent_id')
                    );
            });
    }
}
