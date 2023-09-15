<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;

class LikedFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'liked';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $likedId = $this->asString($filterValue);

        $filterState
            ->getQuery()
            ->whereIn('id', function ($query) use ($likedId) {
                $query->select('user_id')
                    ->from('post_likes')
                    ->where('post_id', $likedId);
            }, 'and', $negate);
    }
}
