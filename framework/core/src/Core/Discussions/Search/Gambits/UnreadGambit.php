<?php namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\DiscussionRepositoryInterface;
use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Search\RegexGambit;
use Flarum\Core\Search\Search;
use LogicException;

class UnreadGambit extends RegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:unread';

    /**
     * @var DiscussionRepositoryInterface
     */
    protected $discussions;

    /**
     * @param DiscussionRepositoryInterface $discussions
     */
    public function __construct(DiscussionRepositoryInterface $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(Search $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $actor = $search->getActor();

        if ($actor->exists) {
            $readIds = $this->discussions->getReadIds($actor);

            $search->getQuery()->where(function ($query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->where('last_time', '>', $actor->read_time ?: 0);
                } else {
                    $query->whereIn('id', $readIds)->orWhere('last_time', '<=', $actor->read_time ?: 0);
                }
            });
        }
    }
}
