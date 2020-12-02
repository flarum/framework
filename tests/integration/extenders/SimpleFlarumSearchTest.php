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
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchCriteria;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class FlarumSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function prepDb()
    {
        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'DISCUSSION 1', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'DISCUSSION 2', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar not the same</p></t>'],
            ],
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);
    }

    public function searchDiscussions($query, $limit = null)
    {
        $actor = User::find(1);

        $criteria = new SearchCriteria($actor, $query);

        return $this->app()->getContainer()->make(DiscussionSearcher::class)->search($criteria, $limit)->getResults();
    }

    /**
     * @test
     */
    public function works_as_expected_with_no_modifications()
    {
        $this->prepDb();

        $searchForAll = json_encode($this->searchDiscussions('foo bar', 5));
        $this->assertContains('DISCUSSION 1', $searchForAll);
        $this->assertContains('DISCUSSION 2', $searchForAll);

        $searchForSecond = json_encode($this->searchDiscussions('not the same', 5));
        $this->assertNotContains('DISCUSSION 1', $searchForSecond);
        $this->assertContains('DISCUSSION 2', $searchForSecond);
    }

    /**
     * @test
     */
    public function custom_full_text_gambit_has_effect_if_added()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->setFullTextGambit(NoResultFullTextGambit::class));

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('foo bar', 5)));
    }

    /**
     * @test
     */
    public function custom_filter_gambit_has_effect_if_added()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->setFullTextGambit(NoResultFilterGambit::class));

        $this->prepDb();

        $withResultSearch = json_encode($this->searchDiscussions('noResult:0', 5));
        $this->assertContains('DISCUSSION 1', $withResultSearch);
        $this->assertContains('DISCUSSION 2', $withResultSearch);
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

        $this->assertEquals('[]', json_encode($this->searchDiscussions('foo bar', 5)));
    }

    /**
     * @test
     */
    public function search_mutator_has_effect_if_added_with_invokable_class()
    {
        $this->extend((new Extend\SimpleFlarumSearch(DiscussionSearcher::class))->addSearchMutator(CustomSearchMutator::class));

        $this->prepDb();

        $this->assertEquals('[]', json_encode($this->searchDiscussions('foo bar', 5)));
    }
}

class NoResultFullTextGambit implements GambitInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $searchValue)
    {
        $search->getQuery()
            ->whereRaw('0=1');
    }
}

class NoResultFilterGambit extends AbstractRegexGambit
{
    protected $pattern = 'noResult:(.+)';

    /**
     * {@inheritdoc}
     */
    public function conditions(AbstractSearch $search, array $matches, $negate)
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
