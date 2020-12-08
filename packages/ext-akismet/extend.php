<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Akismet\Listener;
use Flarum\Akismet\Provider\AkismetProvider;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Extend;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Saving;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Event())
        ->listen(Hidden::class, Listener\SubmitSpam::class)
        ->listen(PostWasApproved::class, Listener\SubmitHam::class)
        ->listen(Saving::class, Listener\ValidatePost::class),

    (new Extend\ServiceProvider())
        ->register(AkismetProvider::class),
];
