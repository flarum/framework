<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Event;

use Flarum\Tags\Tag;
use Flarum\User\User;

class Deleting
{
    /**
     * @var Tag
     */
    public $tag;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Tag $tag
     * @param User $actor
     */
    public function __construct(Tag $tag, User $actor)
    {
        $this->tag = $tag;
        $this->actor = $actor;
    }
}
