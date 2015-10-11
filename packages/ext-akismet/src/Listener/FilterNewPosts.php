<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Core;
use Flarum\Event\PostWillBeSaved;
use Flarum\Flags\Flag;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepository;
use Illuminate\Contracts\Events\Dispatcher;
use TijsVerkoyen\Akismet\Akismet;

class FilterNewPosts
{
    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param SettingsRepository $settings
     * @param Application $app
     */
    public function __construct(SettingsRepository $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'validatePost']);
        $events->listen(PostWasApproved::class, [$this, 'submitHam']);
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function validatePost(PostWillBeSaved $event)
    {
        $post = $event->post;

        if ($post->exists || $post->user->groups()->count()) {
            return;
        }

        $akismet = new Akismet($this->settings->get('flarum-akismet.api_key'), $this->app->url());

        $isSpam = $akismet->isSpam(
            $post->content,
            $post->user->username,
            $post->user->email,
            null,
            'comment'
        );

        if ($isSpam) {
            $post->is_approved = false;

            // TODO:
            // $post->is_spam = true;

            $post->afterSave(function ($post) {
                $flag = new Flag;

                $flag->post_id = $post->id;
                $flag->type = 'akismet';
                $flag->time = time();

                $flag->save();
            });
        }
    }

    /**
     * @param PostWasApproved $event
     */
    public function submitHam(PostWasApproved $event)
    {
        // TODO
        // if ($post->is_spam)
    }
}
