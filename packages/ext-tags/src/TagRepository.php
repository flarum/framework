<?php namespace Flarum\Tags;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\User;
use Flarum\Tags\Tag;

class TagRepository
{
    /**
     * Find all tags, optionally making sure they are visible to a
     * certain user.
     *
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function find(User $user = null)
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
