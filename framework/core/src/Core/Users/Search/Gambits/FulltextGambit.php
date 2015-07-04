<?php namespace Flarum\Core\Users\Search\Gambits;

use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Core\Search\Search;
use Flarum\Core\Search\GambitInterface;

class FulltextGambit implements GambitInterface
{
    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @param UserRepositoryInterface $users
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Search $search, $bit)
    {
        $users = $this->users->getIdsForUsername($bit, $search->getActor());

        $search->getQuery()->whereIn('id', $users);

        $search->setDefaultSort(['id' => $users]);
    }
}
