<?php namespace Flarum\Core\Search\Discussions\Gambits;

use Flarum\Core\Repositories\PostRepositoryInterface;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitInterface;

class FulltextGambit implements GambitInterface
{
    protected $posts;

    public function __construct(PostRepositoryInterface $posts)
    {
        $this->posts = $posts;
    }

    public function apply($string, SearcherInterface $searcher)
    {
        $posts = $this->posts->findByContent($string, $searcher->user);

        $discussions = [];
        foreach ($posts as $post) {
            $discussions[] = $id = $post->discussion_id;
            $searcher->addRelevantPost($id, $post->id);
        }
        $discussions = array_unique($discussions);

        // TODO: implement negate (match for - at start of string)
        $searcher->getQuery()->whereIn('id', $discussions);

        $searcher->setDefaultSort(['id' => $discussions]);
    }
}
