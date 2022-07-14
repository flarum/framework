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

class LikedByFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'likedBy';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $likedId = trim($filterValue, '"');

        $filterState
            ->getQuery()
            ->whereIn('id', function ($query) use ($likedId, $negate) {
                $query->select('post_id')
                    ->from('post_likes')
                    ->where('user_id', $negate ? '!=' : '=', $likedId);
            });
    }
}
