<?php

namespace Flarum\Search;

interface SearcherInterface
{
    public function search(SearchCriteria $criteria, ?int $limit = null, int $offset = 0): SearchResults;
}
