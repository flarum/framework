<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Event;

use Flarum\User\User;

class FlarumUpdated
{
    public const GLOBAL = 'global';
    public const MINOR = 'minor';
    public const MAJOR = 'major';

    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $type;

    public function __construct(User $actor, string $type)
    {
        $this->actor = $actor;
        $this->type = $type;
    }
}
