<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\User\User;

/**
 * @deprecated beta 15, remove beta 16
 */
class GetPermission
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $ability;

    /**
     * @var mixed
     */
    public $model;

    /**
     * @param User $actor
     * @param string $ability
     * @param mixed $model
     */
    public function __construct(User $actor, $ability, $model)
    {
        $this->actor = $actor;
        $this->ability = $ability;
        $this->model = $model;
    }
}
