<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Carbon\Carbon;
use Flarum\Akismet\Akismet;
use Flarum\Flags\Flag;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;

class ValidatePost
{
    public function __construct(
        protected Akismet $akismet,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(Saving $event): void
    {
        if (! $this->akismet->isConfigured()) {
            return;
        }

        $post = $event->post;

        //TODO Sometimes someone posts spam when editing a post. In this case 'recheck_reason=edit' can be used when sending a request to Akismet
        if ($post->exists || ! ($post instanceof CommentPost) || $post->user->hasPermission('bypassAkismet')) {
            return;
        }

        $result = $this->akismet
            ->withContent($post->content)
            ->withAuthorName($post->user->username)
            ->withAuthorEmail($post->user->email)
            ->withType($post->number === 1 ? 'forum-post' : 'reply')
            ->withIp($post->ip_address)
            ->withUserAgent($_SERVER['HTTP_USER_AGENT'])
            ->checkSpam();

        if ($result['isSpam']) {
            $post->is_spam = true;

            if ($result['proTip'] === 'discard' && $this->settings->get('flarum-akismet.delete_blatant_spam')) {
                $post->hide();

                $post->afterSave(function ($post) {
                    if ($post->number == 1) {
                        $post->discussion->hide();
                    }
                });
            } else {
                $post->is_approved = false;

                $post->afterSave(function ($post) {
                    if ($post->number == 1) {
                        $post->discussion->is_approved = false;
                        $post->discussion->save();
                    }

                    $flag = new Flag;

                    $flag->post_id = $post->id;
                    $flag->type = 'akismet';
                    $flag->created_at = Carbon::now();

                    $flag->save();
                });
            }
        }
    }
}
