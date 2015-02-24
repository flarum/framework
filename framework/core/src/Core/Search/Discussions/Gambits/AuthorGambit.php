<?php namespace Flarum\Core\Search\Discussions\Gambits;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Search\Discussions\DiscussionSearcher;
use Flarum\Core\Search\GambitAbstract;

class AuthorGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     * @var string
     */
    protected $pattern = 'author:(.+)';

    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function conditions($matches, DiscussionSearcher $searcher)
    {
        $username = trim($matches[1], '"');

        $id = $this->users->getIdForUsername($username);

        $searcher->query->where('start_user_id', $id);
    }
}
