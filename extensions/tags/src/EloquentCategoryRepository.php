<?php namespace Flarum\Categories;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\User;
use Flarum\Categories\Category;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Find all categories, optionally making sure they are visible to a
     * certain user.
     *
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function find(User $user = null)
    {
        $query = Category::newQuery();

        return $this->scopeVisibleForUser($query, $user)->get();
    }

    /**
     * Get the ID of a category with the given slug.
     *
     * @param string $slug
     * @param \Flarum\Core\Models\User|null $user
     * @return integer
     */
    public function getIdForSlug($slug, User $user = null)
    {
        $query = Category::where('slug', 'like', $slug);

        return $this->scopeVisibleForUser($query, $user)->pluck('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Flarum\Core\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeVisibleForUser(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereCan($user, 'view');
        }

        return $query;
    }
}
