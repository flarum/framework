<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Search;

use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\Filter\RefineFilterInterface;
use Flarum\Search\Filter\WindowFilterInterface;
use Flarum\Search\SearchState;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SearchStateTest extends TestCase
{
    #[Test]
    public function window_filters_are_active_but_do_not_change_which_list_it_is(): void
    {
        $state = new SearchState(new User(), false);
        $tag = $this->filter('tag');
        $since = $this->windowFilter('since');

        $state->addActiveFilter($since);
        $this->assertSame([$since], $state->getActiveFilters());
        $this->assertSame([], $state->getListFilters());

        $state->addActiveFilter($tag);
        $this->assertSame([$since, $tag], $state->getActiveFilters());
        $this->assertSame([$tag], $state->getListFilters());
    }

    #[Test]
    public function refine_filters_are_active_but_do_not_change_which_list_it_is(): void
    {
        $state = new SearchState(new User(), false);
        $tag = $this->filter('tag');
        $date = $this->refineFilter('date');

        $state->addActiveFilter($date);
        $this->assertSame([$date], $state->getActiveFilters());
        $this->assertSame([], $state->getListFilters());

        $state->addActiveFilter($tag);
        $this->assertSame([$date, $tag], $state->getActiveFilters());
        $this->assertSame([$tag], $state->getListFilters());
    }

    protected function filter(string $key): FilterInterface
    {
        return new class($key) implements FilterInterface {
            public function __construct(protected string $key)
            {
            }

            public function getFilterKey(): string
            {
                return $this->key;
            }

            public function filter(SearchState $state, string|array $value, bool $negate): void
            {
            }
        };
    }

    protected function windowFilter(string $key): WindowFilterInterface
    {
        return new class($key) implements WindowFilterInterface {
            public function __construct(protected string $key)
            {
            }

            public function getFilterKey(): string
            {
                return $this->key;
            }

            public function filter(SearchState $state, string|array $value, bool $negate): void
            {
            }
        };
    }

    protected function refineFilter(string $key): RefineFilterInterface
    {
        return new class($key) implements RefineFilterInterface {
            public function __construct(protected string $key)
            {
            }

            public function getFilterKey(): string
            {
                return $this->key;
            }

            public function filter(SearchState $state, string|array $value, bool $negate): void
            {
            }
        };
    }
}
