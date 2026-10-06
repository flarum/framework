<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Ai\AiServiceProvider;
use Flarum\Ai\Listener;
use Flarum\Extend;
use Flarum\Settings\Event\Saved;

return [
    (new Extend\ServiceProvider())
        ->register(AiServiceProvider::class),

    (new Extend\Event())
        ->listen(Saved::class, Listener\ReconfigureAi::class),
];
