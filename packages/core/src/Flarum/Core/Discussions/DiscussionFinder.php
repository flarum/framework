<?php namespace Flarum\Core\Discussions;

use Flarum\Core\Search\Tokenizer;
use Flarum\Core\Posts\Post;

use Cache;
use DB;

class DiscussionFinder
{
    protected $user;

    protected $tokens;

    protected $sort;

    protected $sortMap = [
        'lastPost' => ['last_time', 'desc'],
        'replies'  => ['posts_count', 'desc'],
        'created'  => ['start_time', 'desc']
    ];

    protected $order;

    protected $key;

    protected $count;

    protected $areMoreResults;

    protected $fulltext;

    public function __construct($user = null, $tokens = null, $sort = null, $order = null, $key = null)
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
        return 'discussions.'.$this->key;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }

    public function fulltext()
    {
        return $this->fulltext;
    }

    public function results($count = null, $start = 0, $load = [])
    {
        $relevantPosts = false;

        if (in_array('relevantPosts', $load)) {
            $load = array_diff($load, ['relevantPosts', 'relevantPosts.user']);
            $relevantPosts = true;
        }

        $ids = null;
        $query = Discussion::whereCan($this->user, 'view');
        $query->with($load);

        if ($this->key and Cache::has($key = $this->getCacheKey())) {
            $ids = Cache::get($key);
        } elseif (count($this->tokens)) {
            // foreach ($tokens as $type => $value)
            // {
            //  switch ($type)
            //  {
            //      case 'flag:draft':
            //      case 'flag:muted':
            //      case 'flag:subscribed':
            //      case 'flag:private':
            //          // pre-process
            //          $ids = $this->discussions->getDraftIdsForUser(Auth::user());
            //          $ids = $this->discussions->getMutedIdsForUser(Auth::user());
            //          $ids = $this->discussions->getSubscribedIdsForUser(Auth::user());
            //          $ids = $this->discussions->getPrivateIdsForUser(Auth::user());
            //              // $user->permissions['discussion']['view'] = [1,2,3]
            //          break;
            //  }
            // }

            // $search = $this->search->create();
            // $search->limitToIds($ids);
            // $search->setQuery($query);
            // $search->setSort($sort);
            // $search->setSortOrder($sortOrder);
            // $results = $search->results();

            // process flag:unread here?

            // parse the tokens.
            // run ID filters.

            // TESTING lol
            $this->fulltext = reset($this->tokens);
            $posts = Post::whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$this->fulltext])
                ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$this->fulltext]);

            $posts = $posts->select('id', 'discussion_id');

            $posts = $posts->get();

            $ids = [];
            foreach ($posts as $post) {
                if (empty($ids[$post->discussion_id])) {
                    $ids[$post->discussion_id] = [];
                }
                $ids[$post->discussion_id][] = $post->id;
            }

            if ($this->fulltext and ! $this->sort) {
                $this->sort = 'relevance';
            }

            if (! is_null($ids)) {
                $this->key = str_random();
            }

            // run other tokens
            // $discussions->where('');
        }

        if (! is_null($ids)) {
            Cache::put($this->getCacheKey(), $ids, 10); // recache
            $this->count = count($ids);

            if (! $ids) {
                return [];
            }
            $query->whereIn('id', array_keys($ids));

            // If we're sorting by relevance, assume that the IDs we've been provided
            // are already sorted by relevance. Therefore, we'll get discussions in
            // the order that they are in.
            if ($this->sort == 'relevance') {
                foreach ($ids as $id) {
                    $query->orderBy(DB::raw('id != '.(int) $id));
                }
            }
        }

        if (empty($this->sort)) {
            reset($this->sortMap);
            $this->sort = key($this->sortMap);
        }
        if (! empty($this->sortMap[$this->sort])) {
            list($column, $order) = $this->sortMap[$this->sort];
            $query->orderBy($column, $this->order ?: $order);
        }

        if ($start > 0) {
            $query->skip($start);
        }
        if ($count > 0) {
            $query->take($count + 1);
            $results = $query->get();
            $this->areMoreResults = $results->count() > $count;
            if ($this->areMoreResults) {
                $results->pop();
            }
        } else {
            $results = $query->get();
        }

        if (!empty($relevantPosts)) {
            $postIds = [];
            foreach ($ids as $id => &$posts) {
                $postIds = array_merge($postIds, array_slice($posts, 0, 2));
            }
            $posts = Post::with('user')->whereCan($this->user, 'view')->whereIn('id', $postIds)->get();

            foreach ($results as $discussion) {
                $discussion->relevantPosts = $posts->filter(function ($post) use ($discussion) {
                    return $post->discussion_id == $discussion->id;
                })
                ->slice(0, 2)
                ->each(function ($post) {
                    $pos = strpos(strtolower($post->content), strtolower($this->fulltext));
                    // TODO: make clipping more intelligent (full words only)
                    $start = max(0, $pos - 50);
                    $post->content = ($start > 0 ? '...' : '').str_limit(substr($post->content, $start), 300);
                });
            }
        }

        return $results;
    }
}
