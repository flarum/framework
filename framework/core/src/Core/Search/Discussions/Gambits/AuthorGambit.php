<?php namespace Flarum\Core\Search\Discussions\Gambits;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Search\SearcherInterface;
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

    protected function conditions(SearcherInterface $searcher, array $matches, $negate)
    {
        $username = trim($matches[1], '"');

        $id = $this->users->getIdForUsername($username);

        $searcher->getQuery()->where('start_user_id', $negate ? '!=' : '=', $id);
    }
}
