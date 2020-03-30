<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Search;

use Flarum\Search\AbstractSearch;

/**
 * An object which represents the internal state of a search for posts:
 * the search query, the user performing the search, the fallback sort order,
 * relevant post information, and a log of which gambits have been used.
 */
class PostSearch extends AbstractSearch
{
}
