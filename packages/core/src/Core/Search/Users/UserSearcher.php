<?php namespace Flarum\Core\Search\Users;

use Flarum\Core\Models\User;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Repositories\UserRepositoryInterface;

class UserSearcher implements SearcherInterface
{
    public $query;

    protected $sortMap = [
        'username'    => ['username', 'asc'],
        'posts'       => ['comments_count', 'desc'],
        'discussions' => ['discussions_count', 'desc'],
        'lastActive'  => ['last_seen_time', 'desc'],
        'created'     => ['join_time', 'asc']
    ];

    protected $defaultSort = 'username';

    protected $users;

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
        return $this->query;
    }

    public function search(UserSearchCriteria $criteria, $count = null, $start = 0, $load = [])
    {
        $this->user = $criteria->user;
        $this->query = $this->users->query()->whereCan($criteria->user, 'view');

        $this->gambits->apply($criteria->query, $this);

        $total = $this->query->count();

        $sort = $criteria->sort;
        if (empty($sort)) {
            $sort = $this->defaultSort;
        }
        if (is_array($sort)) {
            foreach ($sort as $id) {
                $this->query->orderByRaw('id != '.(int) $id);
            }
        } else {
            list($column, $order) = $this->sortMap[$sort];
            $this->query->orderBy($column, $criteria->order ?: $order);
        }

        if ($start > 0) {
            $this->query->skip($start);
        }
        if ($count > 0) {
            $this->query->take($count + 1);
        }

        $users = $this->query->get();

        if ($count > 0 && $areMoreResults = $users->count() > $count) {
            $users->pop();
        }

        $users->load($load);

        return new UserSearchResults($users, $areMoreResults, $total);
    }
}
