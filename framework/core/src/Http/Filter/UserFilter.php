<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Api\Controller\ListAccessTokensController;
use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;

/**
 * Filters an access tokens request by the related user.
 *
 * @see ListAccessTokensController
 */
class UserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @inheritDoc
     */
    public function getFilterKey(): string
    {
        return 'user';
    }

    /**
     * @inheritDoc
     */
    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $filterValue = $this->asInt($filterValue);

        $filterState->getQuery()->where('user_id', $negate ? '!=' : '=', $filterValue);
    }
}
