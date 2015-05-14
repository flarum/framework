<?php namespace Flarum\Core\Search\Users;

use Flarum\Core\Models\User;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Events\UserSearchWillBePerformed;

class UserSearcher implements SearcherInterface
{
    protected $query;

    protected $activeGambits = [];

    protected $gambits;

    protected $users;

    protected $defaultSort = ['username' => 'asc'];

    public function __construct(GambitManager $gambits, UserRepositoryInterface $users)
    {
        $this->gambits = $gambits;
        $this->users = $users;
    }

    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }

    public function query()
    {
        return $this->query->getQuery();
    }

    public function addActiveGambit($gambit)
    {
        $this->activeGambits[] = $gambit;
    }

    public function getActiveGambits()
    {
        return $this->activeGambits;
    }

    public function search(UserSearchCriteria $criteria, $limit = null, $offset = 0, $load = [])
    {
        $this->user = $criteria->user;
        $this->query = $this->users->query()->whereCan($criteria->user, 'view');

        $this->gambits->apply($criteria->query, $this);

        $total = $this->query->count();

        $sort = $criteria->sort ?: $this->defaultSort;

        foreach ($sort as $field => $order) {
            if (is_array($order)) {
                foreach ($order as $value) {
                    $this->query->orderByRaw(snake_case($field).' != ?', [$value]);
                }
            } else {
                $this->query->orderBy(snake_case($field), $order);
            }
        }

        if ($offset > 0) {
            $this->query->skip($offset);
        }
        if ($limit > 0) {
            $this->query->take($limit + 1);
        }

        event(new UserSearchWillBePerformed($this, $criteria));

        $users = $this->query->get();

        if ($limit > 0 && $areMoreResults = $users->count() > $limit) {
            $users->pop();
        }

        $users->load($load);

        return new UserSearchResults($users, $areMoreResults, $total);
    }
}
