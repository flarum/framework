<?php namespace Flarum\Tags;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Users\User;
use Flarum\Tags\Tag;

class TagRepository
{
    /**
     * Find a tag by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param int $id
     * @param User $actor
     * @return Tag
     *
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
        $query = Tag::newQuery();

        return $this->scopeVisibleTo($query, $user)->get();
    }

    /**
     * Get the ID of a tag with the given slug.
     *
     * @param string $slug
     * @param User|null $user
     * @return integer
     */
    public function getIdForSlug($slug, User $user = null)
    {
        $query = Tag::where('slug', 'like', $slug);

        return $this->scopeVisibleTo($query, $user)->pluck('id');
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
