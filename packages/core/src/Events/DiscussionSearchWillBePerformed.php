<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Search\SearchCriteria;

class DiscussionSearchWillBePerformed
{
    /**
     * @var DiscussionSearch
     */
    public $search;

    /**
     * @var SearchCriteria
     */
    public $criteria;

    /**
     * @param DiscussionSearch $search
     * @param SearchCriteria $criteria
     */
    public function __construct(DiscussionSearch $search, SearchCriteria $criteria)
    {
        $this->search = $search;
        $this->criteria = $criteria;
    }
}
