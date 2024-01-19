<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\BBCode;

use Flarum\Extend;

return [
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Formatter)
        ->render(Render::class)
        ->configure(Configure::class),
];
