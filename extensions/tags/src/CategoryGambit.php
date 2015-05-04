<?php namespace Flarum\Categories;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class CategoryGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     * @var string
     */
    protected $pattern = 'category:(.+)';

    public function conditions($matches, SearcherInterface $searcher)
    {
        $slug = trim($matches[1], '"');

        // @todo implement categories repository
        // $id = $this->categories->getIdForSlug($slug);
        $id = Category::whereSlug($slug)->pluck('id');

        $searcher->query()->where('category_id', $id);
    }
}
