<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Filter\UnreadFilter;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Flarum\Search\SearchState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SearchDriverTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function prepDb()
    {
        $this->database()->rollBack();

        // We need to insert these outside of a transaction, because FULLTEXT indexing,
        // which is needed for search, doesn't happen in transactions.
        // We clean it up explicitly at the end.
        $this->database()->table('discussions')->insert([
            Discussion::factory()->raw(['id' => 1, 'title' => 'DISCUSSION 1', 'user_id' => 1]),
            Discussion::factory()->raw(['id' => 2, 'title' => 'DISCUSSION 2', 'user_id' => 1]),
        ]);

        $this->database()->table('posts')->insert([
            Post::factory()->raw(['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'content' => '<t><p>not in text</p></t>']),
            Post::factory()->raw(['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'content' => '<t><p>lightsail in text</p></t>']),
        ]);

        // We need to call these again, since we rolled back the transaction started by `::app()`.
        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->whereIn('id', [1, 2])->delete();
        $this->database()->table('posts')->whereIn('id', [1, 2])->delete();
    }

    public function searchDiscussions($query, $limit = null, array $filters = [])
    {
        $this->app();

        $actor = User::find(1);

        $filters['q'] = $query;

        return $this->app()
            ->getContainer()
            ->make(SearchManager::class)
            ->query(Discussion::class, new SearchCriteria($actor, $filters, $limit))
            ->getResults();
    }

    #[Test]
    public function works_as_expected_with_no_modifications()
    {
        $this->prepDb();

        $searchForAll = json_encode($this->searchDiscussions('in text', 5));
        $this->assertStringContainsString('DISCUSSION 1', $searchForAll);
        $this->assertStringContainsString('DISCUSSION 2', $searchForAll);

        $searchForSecond = json_encode($this->searchDiscussions('lightsail', 5));
        $this->assertStringNotContainsString('DISCUSSION 1', $searchForSecond);
        $this->assertStringContainsString('DISCUSSION 2', $searchForSecond);
    }

    #[Test]
    public function custom_full_text_gambit_has_effect_if_added()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->setFulltext(DiscussionSearcher::class, NoResultFullTextFilter::class)
        );

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }

    #[Test]
    public function custom_filter_has_effect_if_added()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(DiscussionSearcher::class, NoResultFilter::class)
        );

        $this->prepDb();

        $withResultSearch = json_encode($this->searchDiscussions('', 5, ['noResult' => '0']));
        $this->assertStringContainsString('DISCUSSION 1', $withResultSearch);
        $this->assertStringContainsString('DISCUSSION 2', $withResultSearch);
        $this->assertEquals('[]', json_encode($this->searchDiscussions('', 5, ['noResult' => '1'])));
    }

    #[Test]
    public function existing_filter_can_be_replaced()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->replaceFilter(DiscussionSearcher::class, UnreadFilter::class, NoResultFilter::class)
        );

        $this->prepDb();

        $this->assertNotContains(UnreadFilter::class, $this->app()->getContainer()->make('flarum.search.filters')[DiscussionSearcher::class]);
        $this->assertContains(NoResultFilter::class, $this->app()->getContainer()->make('flarum.search.filters')[DiscussionSearcher::class]);
        $this->assertEquals('[]', json_encode($this->searchDiscussions('', 5, ['noResult' => '1'])));
    }

    #[Test]
    public function search_mutator_has_effect_if_added()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addMutator(DiscussionSearcher::class, function (DatabaseSearchState $search) {
                    $search->getQuery()->whereRaw('1=0');
                })
        );

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }

    #[Test]
    public function search_mutator_has_effect_if_added_with_invokable_class()
    {
        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addMutator(DiscussionSearcher::class, CustomSearchMutator::class)
        );

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }
}

class NoResultFullTextFilter extends AbstractFulltextFilter
{
    public function search(SearchState $state, string $value): void
    {
        $state->getQuery()->whereRaw('0=1');
    }
}

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class NoResultFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'noResult';
    }

    public function filter(SearchState $state, array|string $value, bool $negate): void
    {
        $noResults = trim($value, ' ');

        if ($noResults == '1') {
            $state->getQuery()->whereRaw('0=1');
        }
    }
}

class CustomSearchMutator
{
    public function __invoke($search, $criteria)
    {
        $search->getQuery()->whereRaw('1=0');
    }
}
