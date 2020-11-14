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

class UnreadFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'unread';
    }

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

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $actor = $wrappedFilter->getActor();

        if ($actor->exists) {
            $readIds = $this->discussions->getReadIds($actor);

            $wrappedFilter->getQuery()->where(function ($query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->where('last_posted_at', '>', $actor->marked_all_as_read_at ?: 0);
                } else {
                    $query->whereIn('id', $readIds)->orWhere('last_posted_at', '<=', $actor->marked_all_as_read_at ?: 0);
                }
            });
        }
    }
}
