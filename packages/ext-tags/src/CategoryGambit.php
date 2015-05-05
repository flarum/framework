<?php namespace Flarum\Categories;

use Flarum\Core\Repositories\UserRepositoryInterface as UserRepository;
use Flarum\Core\Search\SearcherInterface;
use Flarum\Core\Search\GambitAbstract;

class CategoryGambit extends GambitAbstract
{
    /**
     * The gambit's regex pattern.
     *
     * @var string
     */
    protected $pattern = 'category:(.+)';

    /**
     * @var \Flarum\Categories\CategoryRepositoryInterface
     */
    protected $categories;

    /**
     * Instantiate the gambit.
     *
     * @param \Flarum\Categories\CategoryRepositoryInterface $categories
     */
    public function __construct(CategoryRepositoryInterface $categories)
    {
        $this->categories = $categories;
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
        $slug = trim($matches[1], '"');

        $id = $this->categories->getIdForSlug($slug);

        $searcher->query()->where('category_id', $id);
    }
}
