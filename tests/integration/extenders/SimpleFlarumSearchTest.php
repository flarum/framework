<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Query\QueryCriteria;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;

class SimpleFlarumSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function prepDb()
    {
        $this->database()->rollBack();

        // We need to insert these outside of a transaction, because FULLTEXT indexing,
        // which is needed for search, doesn't happen in transactions.
        // We clean it up explcitly at the end.
        $this->database()->table('discussions')->insert([
            ['id' => 1, 'title' => 'DISCUSSION 1', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ['id' => 2, 'title' => 'DISCUSSION 2', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
        ]);

        $this->database()->table('posts')->insert([
            ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
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

    public function searchDiscussions($query, $limit = null)
    {
        $this->app();

        $actor = User::find(1);

        $criteria = new QueryCriteria($actor, ['q' => $query]);

        return $this->app()->getContainer()->make(DiscussionSearcher::class)->search($criteria, $limit)->getResults();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function custom_full_text_gambit_has_effect_if_added()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->setFullTextGambit(NoResultFullTextGambit::class));

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }

    /**
     * @test
     */
    public function custom_filter_gambit_has_effect_if_added()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->addGambit(NoResultFilterGambit::class));

        $this->prepDb();

        $withResultSearch = json_encode($this->searchDiscussions('noResult:0', 5));
        $this->assertStringContainsString('DISCUSSION 1', $withResultSearch);
        $this->assertStringContainsString('DISCUSSION 2', $withResultSearch);
        $this->assertEquals('[]', json_encode($this->searchDiscussions('noResult:1', 5)));
    }

    /**
     * @test
     */
    public function search_mutator_has_effect_if_added()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->addSearchMutator(function ($search, $criteria) {
            $search->getquery()->whereRaw('1=0');
        }));

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }

    /**
     * @test
     */
    public function search_mutator_has_effect_if_added_with_invokable_class()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->addSearchMutator(CustomSearchMutator::class));

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('in text', 5)));
    }

    /**
     * @test
     */
    public function cant_resolve_custom_searcher_without_fulltext_gambit()
    {
        $this->expectException(BindingResolutionException::class);

        $this->app()->getContainer()->make(CustomSearcher::class);
    }

    /**
     * @test
     */
    public function can_resolve_custom_searcher_with_fulltext_gambit()
    {
        $this->extend(
            (new Extend\SimpleFlarumSearch(CustomSearcher::class))->setFullTextGambit(CustomFullTextGambit::class)
        );

        $anExceptionWasThrown = false;

        try {
            $this->app()->getContainer()->make(CustomSearcher::class);
        } catch (BindingResolutionException $e) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }
}

class NoResultFullTextGambit implements GambitInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $searchValue)
    {
        $search->getQuery()
            ->whereRaw('0=1');
    }
}

class NoResultFilterGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'noResult:(.+)';
    }

    /**
     * {@inheritdoc}
     */
    public function conditions(SearchState $search, array $matches, $negate)
    {
        $noResults = trim($matches[1], ' ');
        if ($noResults == '1') {
            $search->getQuery()
                ->whereRaw('0=1');
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

class CustomSearcher extends AbstractSearcher
{
    // This isn't actually used, we just need it to implement the abstract method.
    protected function getQuery(User $actor): Builder
    {
        return Group::query();
    }
}

class CustomFullTextGambit implements GambitInterface
{
    public function apply(SearchState $search, $bit)
    {
    }
}
