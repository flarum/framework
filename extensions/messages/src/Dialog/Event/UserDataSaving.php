<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Dialog\Event;

use Flarum\Messages\UserDialogState;

class UserDataSaving
{
    public function __construct(
        public UserDialogState $state
    ) {
    }
}
