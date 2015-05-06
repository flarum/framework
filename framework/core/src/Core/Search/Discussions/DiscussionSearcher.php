<?php namespace Flarum\Core\Search\Discussions;

use Flarum\Core\Models\Discussion;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Repositories\DiscussionRepositoryInterface;
use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Core\Events\DiscussionSearchWillBePerformed;

class DiscussionSearcher implements SearcherInterface
{
    protected $query;

    protected $relevantPosts = [];

    protected $activeGambits = [];

    protected $gambits;

    protected $discussions;

    protected $defaultSort = ['lastTime' => 'desc'];

    public function __construct(GambitManager $gambits, DiscussionRepositoryInterface $discussions, PostRepositoryInterface $posts)
    {
        $this->gambits = $gambits;
        $this->discussions = $discussions;
        $this->posts = $posts;
    }

    public function addRelevantPost($discussionId, $postId)
    {
        if (empty($this->relevantPosts[$discussionId])) {
            $this->relevantPosts[$discussionId] = [];
        }
        $this->relevantPosts[$discussionId][] = $postId;
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

    public function search(DiscussionSearchCriteria $criteria, $limit = null, $offset = 0, $load = [])
    {
        $this->user = $criteria->user;
        $this->query = $this->discussions->query()->whereCan($criteria->user, 'view');

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

        event(new DiscussionSearchWillBePerformed($this, $criteria));

        $discussions = $this->query->get();

        if ($limit > 0 && $areMoreResults = $discussions->count() > $limit) {
            $discussions->pop();
        }

        if (in_array('relevantPosts', $load) && count($this->relevantPosts)) {
            $load = array_diff($load, ['relevantPosts']);

            $postIds = [];
            foreach ($this->relevantPosts as $id => $posts) {
                $postIds = array_merge($postIds, array_slice($posts, 0, 2));
            }
            $posts = $this->posts->findByIds($postIds, $this->user)->load('user');

            foreach ($discussions as $discussion) {
                $discussion->relevantPosts = $posts->filter(function ($post) use ($discussion) {
                    return $post->discussion_id == $discussion->id;
                })
                ->each(function ($post) {
                    $pos = strpos(strtolower($post->content), strtolower($this->fulltext));
                    // TODO: make clipping more intelligent (full words only)
                    $start = max(0, $pos - 50);
                    $post->content = ($start > 0 ? '...' : '').str_limit(substr($post->content, $start), 300);
                });
            }
        }

        // @todo make instance rather than static and set on all discussions
        Discussion::setStateUser($this->user);
        $discussions->load($load);

        return new DiscussionSearchResults($discussions, $areMoreResults, $total);
    }
}
