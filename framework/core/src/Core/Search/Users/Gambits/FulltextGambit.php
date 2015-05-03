<?php namespace Flarum\Core\Search\Users\Gambits;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class FulltextGambit extends GambitAbstract
{
    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function apply($string, SearcherInterface $searcher)
    {
        $users = $this->users->getIdsForUsername($string, $searcher->user);

        $searcher->query()->whereIn('id', $users);

        $searcher->setDefaultSort(['id' => $users]);
    }
}
