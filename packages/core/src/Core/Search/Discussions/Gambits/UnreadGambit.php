<?php namespace Flarum\Core\Search\Discussions\Gambits;

use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class UnreadGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     * @var string
     */
    protected $pattern = 'is:unread';

    protected $discussions;

    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    protected function conditions(SearcherInterface $searcher, array $matches, $negate)
    {
        $user = $searcher->user;

        if ($user->exists) {
            $readIds = $this->discussions->getReadIds($user);

            if (! $negate) {
                $searcher->getQuery()->whereNotIn('id', $readIds)->where('last_time', '>', $user->read_time ?: 0);
            } else {
                $searcher->getQuery()->whereIn('id', $readIds)->orWhere('last_time', '<=', $user->read_time ?: 0);
            }
        }
    }
}
