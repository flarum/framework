<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class TagRepository
{
    private const TAG_RELATIONS = ['children', 'parent'];

    /**
     * Get a new query builder for the tags table.
     *
     * @return Builder
     */
    public function query()
    {
        return Tag::query();
    }

    /**
     * @param array|string $relations
     * @param User $actor
     * @return Builder
     */
    public function with($relations, User $actor): Builder
    {
        $relations = is_string($relations) ? explode(',', $relations) : $relations;
        $relationsArray = [];

        foreach ($relations as $relation) {
            if (in_array($relation, self::TAG_RELATIONS, true)) {
                $relationsArray[$relation] = function ($query) use ($actor) {
                    $query->whereVisibleTo($actor);
                };
            } else {
                $relationsArray[] = $relation;
            }
        }

        return $this->query()->with($relationsArray);
    }

    /**
     * Find a tag by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User $actor
     * @return Tag
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = Tag::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find all tags, optionally making sure they are visible to a
     * certain user.
     *
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(User $user = null)
    {
        $query = Tag::query();

        return $this->scopeVisibleTo($query, $user)->get();
    }

    /**
     * Get the ID of a tag with the given slug.
     *
     * @param string $slug
     * @param User|null $user
     * @return int
     */
    public function getIdForSlug($slug, User $user = null): ?int
    {
        $query = Tag::where('slug', $slug);

        return $this->scopeVisibleTo($query, $user)->value('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function scopeVisibleTo(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
