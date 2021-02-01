<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class UnreadFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * @var \Flarum\Discussion\DiscussionRepository
     */
    protected $discussions;

    /**
     * @param \Flarum\Discussion\DiscussionRepository $discussions
     */
    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:unread';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'unread';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $this->constrain($wrappedFilter->getQuery(), $wrappedFilter->getActor(), $negate);
    }

    protected function constrain(Builder $query, User $actor, bool $negate)
    {
        if ($actor->exists) {
            $readIds = $this->discussions->getReadIds($actor);

            $query->where(function ($query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->where('last_posted_at', '>', $actor->marked_all_as_read_at ?: 0);
                } else {
                    $query->whereIn('id', $readIds)->orWhere('last_posted_at', '<=', $actor->marked_all_as_read_at ?: 0);
                }
            });
        }
    }
}
