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
 * A filter a person adds to narrow the list they are looking at, such as a
 * date range or a language picked on the All Discussions page, rather than a
 * filter that asks for a different list. The list stays the same list: its
 * mutators still apply, including pinning sticky discussions, as they do for
 * the list without the filter.
 *
 * Unlike `WindowFilterInterface`, which a client adds to ask about part of a
 * list it already shows, a refined list is still one a person reads in its
 * usual order. A filter implementing both counts as a window filter.
 *
 * Code that decides which list a request is looks at
 * `SearchState::getListFilters()`, which leaves these filters out.
 *
 * @template TState of SearchState
 * @extends FilterInterface<TState>
 */
interface RefineFilterInterface extends FilterInterface
{
}
