<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Discussions\Search\Fulltext\Driver;
use Flarum\Core\Search\Search;
use Flarum\Core\Search\Gambit;
use LogicException;

class FulltextGambit implements Gambit
{
    /**
     * @var Driver
     */
    protected $fulltext;

    /**
     * @param Driver $fulltext
     */
    public function __construct(Driver $fulltext)
    {
        $this->fulltext = $fulltext;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Search $search, $bit)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $relevantPostIds = $this->fulltext->match($bit);

        $discussionIds = array_keys($relevantPostIds);

        $search->setRelevantPostIds($relevantPostIds);

        $search->getQuery()->whereIn('id', $discussionIds);

        $search->setDefaultSort(['id' => $discussionIds]);
    }
}
