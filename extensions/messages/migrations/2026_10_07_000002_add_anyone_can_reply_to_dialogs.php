<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

// Set once someone with the "Message users without messaging permission"
// permission sends a message in the dialog: from then on, every member can
// reply in it, whatever their own groups allow.
return Migration::addColumns('dialogs', [
    'anyone_can_reply' => ['boolean', 'default' => false],
]);
