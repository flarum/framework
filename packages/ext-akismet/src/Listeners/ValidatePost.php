<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listeners;

use Flarum\Events\PostWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;
use TijsVerkoyen\Akismet\Akismet;
use Flarum\Core;
use Flarum\Core\Posts\CommentPost;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Flags\Flag;
use Flarum\Approval\Events\PostWasApproved;

class ValidatePost
{
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'validatePost']);
        $events->listen(PostWasApproved::class, [$this, 'submitHam']);
    }

    public function validatePost(PostWillBeSaved $event)
    {
        $post = $event->post;

        if ($post->exists || $post->user->groups()->count()) {
            return;
        }

        $akismet = new Akismet($this->settings->get('akismet.api_key'), Core::url());

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

    public function submitHam(PostWasApproved $event)
    {
        // TODO
        // if ($post->is_spam)
    }
}
