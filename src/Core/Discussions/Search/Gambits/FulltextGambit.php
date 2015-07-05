<?php namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Posts\PostRepository;
use Flarum\Core\Search\Search;
use Flarum\Core\Search\Gambit;
use LogicException;

class FulltextGambit implements Gambit
{
    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Search $search, $bit)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $posts = $this->posts->findByContent($bit, $search->getActor());

        $discussions = [];
        foreach ($posts as $post) {
            $discussions[] = $id = $post->discussion_id;
            $search->addRelevantPostId($id, $post->id);
        }
        $discussions = array_unique($discussions);

        // TODO: implement negate (match for - at start of string)
        $search->getQuery()->whereIn('id', $discussions);

        $search->setDefaultSort(['id' => $discussions]);
    }
}
