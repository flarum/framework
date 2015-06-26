<?php namespace Flarum\Core\Search\Users\Gambits;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitInterface;

class FulltextGambit implements GambitInterface
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function apply($string, SearcherInterface $searcher)
    {
        $users = $this->users->getIdsForUsername($string, $searcher->user);

        $searcher->getQuery()->whereIn('id', $users);

        $searcher->setDefaultSort(['id' => $users]);
    }
}
