<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Extend;
use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class FilterTest extends TestCase
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
                $this->normalUser(),
            ],
        ]);
    }

    public function filterDiscussions($filters, $limit = null)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => $filters,
                'include' => 'mostRelevantPost',
            ])
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    /**
     * @test
     */
    public function works_as_expected_with_no_modifications()
    {
        $this->prepDb();

        $searchForAll = json_encode($this->filterDiscussions([], 5));
        $this->assertStringContainsString('DISCUSSION 1', $searchForAll);
        $this->assertStringContainsString('DISCUSSION 2', $searchForAll);
    }

    /**
     * @test
     */
    public function custom_filter_has_effect_if_added()
    {
        $this->extend((new Extend\Filter(DiscussionFilterer::class))->addFilter(NoResultFilter::class));

        $this->prepDb();

        $withResultSearch = json_encode($this->filterDiscussions(['noResult' => 0], 5));
        $this->assertStringContainsString('DISCUSSION 1', $withResultSearch);
        $this->assertStringContainsString('DISCUSSION 2', $withResultSearch);
        $this->assertEquals([], $this->filterDiscussions(['noResult' => 1], 5));
    }

    /**
     * @test
     */
    public function filter_mutator_has_effect_if_added()
    {
        $this->extend((new Extend\Filter(DiscussionFilterer::class))->addFilterMutator(function ($filterState, $criteria) {
            $filterState->getQuery()->whereRaw('1=0');
        }));

        $this->prepDb();

        $this->assertEquals([], $this->filterDiscussions([], 5));
    }

    /**
     * @test
     */
    public function filter_mutator_has_effect_if_added_with_invokable_class()
    {
        $this->extend((new Extend\Filter(DiscussionFilterer::class))->addFilterMutator(CustomFilterMutator::class));

        $this->prepDb();

        $this->assertEquals([], $this->filterDiscussions([], 5));
    }
}

class NoResultFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'noResult';
    }

    /**
     * {@inheritdoc}
     */
    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        if ($filterValue) {
            $filterState->getQuery()
                ->whereRaw('0=1');
        }
    }
}

class CustomFilterMutator
{
    public function __invoke($filterState, $criteria)
    {
        $filterState->getQuery()->whereRaw('1=0');
    }
}
