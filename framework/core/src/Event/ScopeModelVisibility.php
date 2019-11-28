<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopeModelVisibility` event allows constraints to be applied in a query
 * to fetch a model, effectively scoping that model's visibility to the user.
 */
class ScopeModelVisibility
{
    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $ability;

    /**
     * @param Builder $query
     * @param User $actor
     * @param string $ability
     */
    public function __construct(Builder $query, User $actor, $ability)
    {
        $this->query = $query;
        $this->actor = $actor;
        $this->ability = $ability;
    }
}
