<?php namespace Flarum\Core\Search\Discussions\Gambits;

use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Search\Discussions\DiscussionSearcher;
use Flarum\Core\Search\GambitAbstract;

class UnreadGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     * @var string
     */
    protected $pattern = 'unread:(true|false)';

    protected $discussions;

    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    protected function conditions($matches, DiscussionSearcher $searcher)
    {
        $user = $searcher->user;

        if ($user->exists) {
            $readIds = $this->discussions->getReadIds($user);

            if ($matches[1] === 'true') {
                $searcher->query->whereNotIn('id', $readIds)->where('last_time', '>', $user->read_time ?: 0);
            } else {
                $searcher->query->whereIn('id', $readIds)->orWhere('last_time', '<=', $user->read_time ?: 0);
            }
        }
    }
}
