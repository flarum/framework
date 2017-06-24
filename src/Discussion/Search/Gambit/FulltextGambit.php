<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Gambit;

use Flarum\Discussion\Search\DiscussionSearch;
use Flarum\Discussion\Search\Fulltext\DriverInterface;
use Flarum\Search\AbstractSearch;
use Flarum\Search\GambitInterface;
use LogicException;

class FulltextGambit implements GambitInterface
{
    /**
     * @var \Flarum\Discussion\Search\Fulltext\DriverInterface
     */
    protected $fulltext;

    /**
     * @param \Flarum\Discussion\Search\Fulltext\DriverInterface $fulltext
     */
    public function __construct(DriverInterface $fulltext)
    {
        $this->fulltext = $fulltext;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
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
