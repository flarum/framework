<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion\Gambit;

use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\Discussion\DiscussionSearch;
use Flarum\Core\Search\Discussion\Driver\DriverInterface;
use Flarum\Core\Search\Discussion\Driver\MySqlDiscussionTitleDriver;
use Flarum\Core\Search\GambitInterface;
use LogicException;

class TitleGambit implements GambitInterface
{
    /**
     * @var MySqlDiscussionTitleDriver
     */
    protected $title;

    /**
     * @param MySqlDiscussionTitleDriver $title
     */
    public function __construct(MySqlDiscussionTitleDriver $title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $relevantPostIds = $this->title->match($bit);

        $discussionIds = array_keys($relevantPostIds);

        $old_relevantPostIds = $search->getRelevantPostIds();

        error_log("Old relevant posts: " + sizeof($old_relevantPostIds));

        $relevantPostIds = array_merge($relevantPostIds, $old_relevantPostIds);

        error_log("New relevant posts: " + sizeof($relevantPostIds));

        $search->setRelevantPostIds($relevantPostIds);

        $search->getQuery()->whereIn('id', $discussionIds);

        $search->setDefaultSort(['id' => $discussionIds]);
    }
}
