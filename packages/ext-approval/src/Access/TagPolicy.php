<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\Tags\Tag;
use Flarum\User\AbstractPolicy;
use Flarum\User\User;

class TagPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Tag::class;

    /**
     * @param User $actor
     * @param Tag $tag
     * @return bool|null
     */
    public function addToDiscussion(User $actor, Tag $tag)
    {
        static $disallowedTags;

        if (! isset($disallowedTags[$actor->id])) {
            $disallowedTags[$actor->id] = Tag::getIdsWhereCannot($actor, 'discussion.startWithoutApproval');
        }

        if (in_array($tag->id, $disallowedTags)) {
            return false;
        }
    }
}
