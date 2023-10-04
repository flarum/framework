<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\SearchState;

class FilterManager
{
    /**
     * @var array<string, FilterInterface[]>
     */
    protected array $filters = [];

    public function __construct(
        protected ?AbstractFulltextFilter $fulltext = null
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
        if ($this->fulltext && $query) {
            $search->addActiveFilter($this->fulltext);
            $this->fulltext->search($search, $query);
        }
    }

    public function getFulltext(): ?AbstractFulltextFilter
    {
        return $this->fulltext;
    }
}
