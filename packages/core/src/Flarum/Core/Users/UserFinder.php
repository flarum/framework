<?php namespace Flarum\Core\Users;

use Flarum\Core\Search\Tokenizer;

use Cache;

class UserFinder
{
    protected $user;

    protected $tokens;

    protected $sort;

    protected $sortMap = [
        'username'     => ['username', 'asc'],
        'posts'        => ['count_posts', 'desc'],
        'discussions'  => ['count_discussions', 'desc'],
        'last_active'  => ['last_action_time', 'desc'],
        'created'      => ['join_time', 'asc']
    ];

    protected $order;

    protected $key;

    protected $count;
    
    protected $areMoreResults;

    public function __construct($user = null, $tokens = null, $sort = null, $order = '', $key = null)
    {
        $this->user = $user;
        $this->tokens = $tokens;
        $this->sort = $sort;
        $this->order = $order;
        $this->key = $key;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    public function setQuery($query)
    {
        $tokenizer = new Tokenizer($query);
        $this->setTokens($tokenizer->tokenize());
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    protected function getCacheKey()
    {
        return 'users.'.$this->key;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }

    public function results($count = null, $start = 0)
    {
        $ids = null;
        $query = User::whereCan($this->user, 'view');

        // not sure if we need any of this stuff - especially ID filters?

        // if ($this->key and Cache::has($key = $this->getCacheKey()))
        // {
        // 	$ids = Cache::get($key);
        // }
        // elseif (count($this->tokens))
        // {
        // 	// parse the tokens.
        // 	// run ID filters.
        // 	/*
        // 	for fulltext token:
        // 	if ( ! $this->sort) $this->sort = 'relevance';
        // 	*/
        // 	if ( ! is_null($ids))
        // 	{
        // 		$this->key = str_random();
        // 	}

        // 	// run other tokens
        // 	// $discussions->where('');
        // }

        // if ( ! is_null($ids))
        // {
        // 	Cache::put($this->getCacheKey(), $ids, 10); // recache
        // 	$this->count = count($ids);

        // 	if ( ! $ids) return false;
        // 	$query->whereIn('id', $ids);
        // }

        $this->count = (int) $query->count();

        if (empty($this->sort)) {
            reset($this->sortMap);
            $this->sort = key($this->sortMap);
        }
        if (! empty($this->sortMap[$this->sort])) {
            list($column, $order) = $this->sortMap[$this->sort];
            $query->orderBy($column, $this->order ?: $order);
        }

        if ($start > 0) {
            $query->skip($count);
        }
        if ($count > 0) {
            $query->take($count);
        }
        return $query->get();
    }
}
