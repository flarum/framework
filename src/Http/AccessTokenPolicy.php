<?php

namespace Flarum\Http;

use Flarum\User\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenPolicy extends AbstractPolicy
{
    protected $model = AccessToken::class;

    public function delete(User $actor, AccessToken $token)
    {
        return $token->user_id === $actor->id;
    }

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        if (!$actor->isAdmin()) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                $query->where('user_id', $actor->id);
            }
        }
    }
}
