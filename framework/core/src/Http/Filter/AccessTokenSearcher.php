<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Http\AccessToken;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\FilterManager;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenSearcher extends AbstractSearcher
{
    public function __construct(FilterManager $filters, array $mutators)
    {
        parent::__construct($filters, $mutators);
    }

    protected function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
