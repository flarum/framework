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

class Creating
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
     * @var array
     */
    public $data;

    /**
     * @param Tag $tag
     * @param User $actor
     * @param array $data
     */
    public function __construct(Tag $tag, User $actor, array $data)
    {
        $this->tag = $tag;
        $this->actor = $actor;
        $this->data = $data;
    }
}
