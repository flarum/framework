<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

// A name members may give a group, where the forum allows it. Without one, a
// group is titled by its members.
return Migration::addColumns('dialogs', [
    'name' => ['string', 'length' => 100, 'nullable' => true],
]);
