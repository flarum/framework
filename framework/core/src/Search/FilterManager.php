<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

class FilterManager
{
    /**
     * @var array<string, FilterInterface[]>
     */
    protected array $filters = [];

    public function __construct(
        protected ?AbstractFulltextFilter $fulltextGambit = null
    ) {
    }

    public function add(FilterInterface $filter): void
    {
        $this->filters[$filter->getFilterKey()][] = $filter;
    }

    public function apply(SearchState $search, array $filters): void
    {
        $this->applyFulltext($search, $filters['q'] ?? null);
        $this->applyFilters($search, $filters);
    }

    protected function applyFilters(SearchState $search, array $filters): void
    {
        foreach ($filters as $filterKey => $filterValue) {
            $negate = false;

            if (str_starts_with($filterKey, '-')) {
                $negate = true;
                $filterKey = substr($filterKey, 1);
            }

            foreach (($this->filters[$filterKey] ?? []) as $filter) {
                $search->addActiveFilter($filter);
                $filter->filter($search, $filterValue, $negate);
            }
        }
    }

    protected function applyFulltext(SearchState $search, ?string $query): void
    {
        if ($this->fulltextGambit && $query) {
            $search->addActiveFilter($this->fulltextGambit);
            $this->fulltextGambit->search($search, $query);
        }
    }
}
