<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions\Search;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Search\AppliesParametersToSearch;
use Flarum\Core\Search\SearchCriteria;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\PostRepository;
use Flarum\Events\DiscussionSearchWillBePerformed;
use Flarum\Core\Search\SearchResults;
use Illuminate\Database\Eloquent\Collection;

/**
 * Takes a DiscussionSearchCriteria object, performs a search using gambits,
 * and spits out a DiscussionSearchResults object.
 */
class DiscussionSearcher
{
    use AppliesParametersToSearch;

    /**
     * @var GambitManager
     */
    protected $gambits;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param GambitManager $gambits
     * @param DiscussionRepository $discussions
     * @param PostRepository $posts
     */
    public function __construct(
        GambitManager $gambits,
        DiscussionRepository $discussions,
        PostRepository $posts
    ) {
        $this->gambits = $gambits;
        $this->discussions = $discussions;
        $this->posts = $posts;
    }

    /**
     * @param SearchCriteria $criteria
     * @param int|null $limit
     * @param int $offset
     * @param array $load An array of relationships to load on the results.
     * @return SearchResults
     */
    public function search(SearchCriteria $criteria, $limit = null, $offset = 0, array $load = [])
    {
        $actor = $criteria->actor;

        $query = $this->discussions->query()->whereVisibleTo($actor);

        // Construct an object which represents this search for discussions.
        // Apply gambits to it, sort, and paging criteria. Also give extensions
        // an opportunity to modify it.
        $search = new DiscussionSearch($query->getQuery(), $actor);

        $this->gambits->apply($search, $criteria->query);
        $this->applySort($search, $criteria->sort);
        $this->applyOffset($search, $offset);
        $this->applyLimit($search, $limit + 1);

        event(new DiscussionSearchWillBePerformed($search, $criteria));

        // Execute the search query and retrieve the results. We get one more
        // results than the user asked for, so that we can say if there are more
        // results. If there are, we will get rid of that extra result.
        $discussions = $query->get();

        if ($areMoreResults = ($limit > 0 && $discussions->count() > $limit)) {
            $discussions->pop();
        }

        // The relevant posts relationship isn't a typical Eloquent
        // relationship; rather, we need to extract that information from our
        // search object. We will delegate that task and prevent Eloquent
        // from trying to load it.
        if (in_array('relevantPosts', $load)) {
            $this->loadRelevantPosts($discussions, $search);

            $load = array_diff($load, ['relevantPosts', 'relevantPosts.discussion', 'relevantPosts.user']);
        }

        Discussion::setStateUser($actor);
        $discussions->load($load);

        return new SearchResults($discussions, $areMoreResults);
    }

    /**
     * Load relevant posts onto each discussion using information from the
     * search.
     *
     * @param Collection $discussions
     * @param DiscussionSearch $search
     */
    protected function loadRelevantPosts(Collection $discussions, DiscussionSearch $search)
    {
        $postIds = [];
        foreach ($search->getRelevantPostIds() as $relevantPostIds) {
            $postIds = array_merge($postIds, array_slice($relevantPostIds, 0, 2));
        }

        $posts = $postIds ? $this->posts->findByIds($postIds, $search->getActor())->load('user')->all() : [];

        foreach ($discussions as $discussion) {
            $discussion->relevantPosts = array_filter($posts, function ($post) use ($discussion) {
                return $post->discussion_id == $discussion->id;
            });
        }
    }
}
