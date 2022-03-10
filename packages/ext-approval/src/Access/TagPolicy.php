<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\Tags\Tag;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class TagPolicy extends AbstractPolicy
{
    /**
     * @return bool|null
     */
    public function addToDiscussion(User $actor, Tag $tag)
    {
        return $actor->can('discussion.startWithoutApproval', $tag);
    }
}
