<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;

class UserSuspendedBlueprint implements BlueprintInterface
{
    /**
     * @var User
     */
    public $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getFromUser()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->user->suspended_until;
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'userSuspended';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return User::class;
    }
}
