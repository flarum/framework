<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Extension\Event;

use Flarum\Extension\Extension;
use Flarum\User\User;

class Updated
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var Extension
     */
    public $extension;

    public function __construct(User $actor, Extension $extension)
    {
        $this->actor = $actor;
        $this->extension = $extension;
    }
}
