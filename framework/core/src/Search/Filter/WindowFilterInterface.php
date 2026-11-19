<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Flarum\Search\SearchState;

/**
 * A filter a client adds to ask about part of a list it already shows, such
 * as Deck asking which discussions in a column have been active since a given
 * time, rather than a filter that asks for a different list. Its presence
 * alone doesn't stop a request counting as the unfiltered list (for example,
 * the All Discussions page), so that list's mutators still apply.
 *
 * Filters people choose, such as `created:` in a search, and lookups such as
 * `id` are list filters: implement `FilterInterface` for those. A filter a
 * person picks to narrow the list they are on, such as a date range on the
 * All Discussions page, implements `RefineFilterInterface`.
 *
 * Code that decides which list a request is looks at
 * `SearchState::getListFilters()`, which leaves these filters out.
 *
 * @template TState of SearchState
 * @extends FilterInterface<TState>
 */
interface WindowFilterInterface extends FilterInterface
{
}
