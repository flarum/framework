<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Event;

use Flarum\Notification\Blueprint\BlueprintInterface;

class Sending
{
    /**
     * The blueprint for the notification.
     *
     * @var BlueprintInterface
     */
    public $blueprint;

    /**
     * The users that the notification will be sent to.
     *
     * @var array
     */
    public $users;

    /**
     * @param \Flarum\Notification\Blueprint\BlueprintInterface $blueprint
     * @param \Flarum\User\User[] $users
     */
    public function __construct(BlueprintInterface $blueprint, array &$users)
    {
        $this->blueprint = $blueprint;
        $this->users = &$users;
    }
}
