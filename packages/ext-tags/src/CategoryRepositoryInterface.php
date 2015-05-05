<?php namespace Flarum\Categories;

use Flarum\Core\Models\User;

interface CategoryRepositoryInterface
{
    /**
     * Find all categories, optionally making sure they are visible to a
     * certain user.
     *
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function find(User $user = null);

    /**
     * Get the ID of a category with the given slug.
     *
     * @param string $slug
     * @param \Flarum\Core\Models\User|null $user
     * @return integer
     */
    public function getIdForSlug($slug, User $user = null);
}
