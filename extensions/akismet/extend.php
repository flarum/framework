<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Akismet\Listener;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Extend;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use TijsVerkoyen\Akismet\Akismet;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    function (Dispatcher $events, Container $container) {
        $container->bind(Akismet::class, function ($app) {
            $settings = $app->make(SettingsRepositoryInterface::class);

            return new Akismet(
                $settings->get('flarum-akismet.api_key'),
                $app->url()
            );
        });

        $events->listen(Saving::class, Listener\ValidatePost::class);
        $events->listen(PostWasApproved::class, Listener\SubmitHam::class);
        $events->listen(Hidden::class, Listener\SubmitSpam::class);
    },
];
