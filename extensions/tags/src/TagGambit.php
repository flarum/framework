<?php namespace Flarum\Tags;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class TagGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     *
     * @var string
     */
    protected $pattern = 'tag:(.+)';

    /**
     * @var \Flarum\Tags\TagRepositoryInterface
     */
    protected $tags;

    /**
     * Instantiate the gambit.
     *
     * @param \Flarum\Tags\TagRepositoryInterface $categories
     */
    public function __construct(TagRepositoryInterface $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Apply conditions to the searcher, given matches from the gambit's
     * regex.
     *
     * @param array $matches The matches from the gambit's regex.
     * @param \Flarum\Core\Search\SearcherInterface $searcher
     * @return void
     */
    public function conditions($matches, SearcherInterface $searcher)
    {
        $slugs = explode(',', trim($matches[1], '"'));

        $searcher->query()->where(function ($query) use ($slugs) {
            foreach ($slugs as $slug) {
                if ($slug === 'uncategorized') {
                    $query->orWhereNotExists(function ($query) {
                        $query->select(app('db')->raw(1))
                              ->from('discussions_tags')
                              ->whereRaw('discussion_id = discussions.id');
                    });
                } else {
                    $id = $this->tags->getIdForSlug($slug);

                    $query->orWhereExists(function ($query) use ($id) {
                        $query->select(app('db')->raw(1))
                              ->from('discussions_tags')
                              ->whereRaw('discussion_id = discussions.id AND tag_id = ?', [$id]);
                    });
                }
            }
        });
    }
}
