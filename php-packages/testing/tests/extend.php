<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing;

use Flarum\Extend;

return [
    (new Extend\Settings)->serializeToForum('notARealSetting', 'not.a.real.setting'),
    (new Extend\Frontend('forum'))->route('/added-by-extension', 'added-by-extension')
];
