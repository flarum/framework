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
use Illuminate\Database\Eloquent\Collection;

class TagRepository
{
    private const TAG_RELATIONS = ['children', 'parent', 'parent.children'];

    /**
     * @return Builder<Tag>
     */
    public function query(): Builder
    {
        return Tag::query();
    }

    public function queryVisibleTo(?User $actor = null): Builder
    {
        return $this->scopeVisibleTo($this->query(), $actor);
    }

    /**
     * @return Builder<Tag>
     */
    public function with(array|string $relations, User $actor): Builder
    {
        return $this->query()->with($this->getAuthorizedRelations($relations, $actor));
    }

    public function getAuthorizedRelations(array|string $relations, User $actor): array
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

        return $relationsArray;
    }

    /**
     * Find a tag by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     */
    public function findOrFail(int $id, User $actor = null): Tag
    {
        $query = Tag::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * Find all tags, optionally making sure they are visible to a
     * certain user.
     *
     * @return Collection<Tag>
     */
    public function all(User $user = null): Collection
    {
        $query = Tag::query();

        return $this->scopeVisibleTo($query, $user)->get();
    }

    /**
     * Get the ID of a tag with the given slug.
     */
    public function getIdForSlug(string $slug, User $user = null): ?int
    {
        $query = Tag::where('slug', $slug);

        return $this->scopeVisibleTo($query, $user)->value('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder<Tag> $query
     * @return Builder<Tag>
     */
    protected function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }
}
