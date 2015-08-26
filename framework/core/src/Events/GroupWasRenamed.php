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

use Flarum\Core\Groups\Group;

class GroupWasRenamed
{
    /**
     * The group that was renamed.
     *
     * @var Group
     */
    public $group;

    /**
     * @param Group $group The group that was renamed.
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
