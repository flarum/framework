<?php

namespace Flarum\Search;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

interface SearcherInterface
{
    function getQuery(User $actor): Builder;

    function search(SearchCriteria $criteria): SearchResults;
}
