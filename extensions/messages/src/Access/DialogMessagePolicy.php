<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Access;

use Flarum\Messages\DialogMessage;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DialogMessagePolicy extends AbstractPolicy
{
    public function update(User $actor, DialogMessage $dialogMessage): bool
    {
        return false;
    }
}
