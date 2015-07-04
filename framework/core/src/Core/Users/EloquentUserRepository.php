<?php namespace Flarum\Core\Users;

use Illuminate\Database\Eloquent\Builder;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function query()
    {
        return User::query();
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail($id, User $actor = null)
    {
        $query = User::where('id', $id);

        return $this->scopeVisibleTo($query, $actor)->firstOrFail();
    }

    /**
     * {@inheritdoc}
     */
    public function findByIdentification($identification)
    {
        $field = filter_var($identification, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return User::where($field, $identification)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdForUsername($username, User $actor = null)
    {
        $query = User::where('username', 'like', $username);

        return $this->scopeVisibleTo($query, $actor)->pluck('id');
    }

    /**
     * {@inheritdoc}
     */
    public function getIdsForUsername($string, User $actor = null)
    {
        $query = User::where('username', 'like', '%'.$string.'%')
            ->orderByRaw('username = ? desc', [$string])
            ->orderByRaw('username like ? desc', [$string.'%']);

        return $this->scopeVisibleTo($query, $actor)->lists('id');
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $actor
     * @return Builder
     */
    protected function scopeVisibleTo(Builder $query, User $actor = null)
    {
        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        return $query;
    }
}
