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
use Flarum\Reports\Report;

class ValidatePost
{
    protected $settings;

    private $savingPost;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'validatePost']);
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
            $post->hide();

            $this->savingPost = $post;

            CommentPost::saved(function (CommentPost $post) {
                if ($post !== $this->savingPost) {
                    return;
                }

                $report = new Report;

                $report->post_id = $post->id;
                $report->reporter = 'Akismet';
                $report->reason = 'spam';
                $report->time = time();

                $report->save();

                $this->savingPost = null;
            });
        }
    }
}
