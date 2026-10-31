<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Ai\AiServiceProvider;
use Flarum\Ai\Content;
use Flarum\Ai\Listener;
use Flarum\Extend;
use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less')
        ->content(Content\AdminPayload::class),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(AiServiceProvider::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\PrepareConnections::class)
        ->listen(Saved::class, Listener\ReconfigureAi::class)
        ->listen(Deserializing::class, Listener\HideApiKeys::class),
];
