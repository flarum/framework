<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use TijsVerkoyen\Akismet\Akismet;

class ValidatePost
{
    /**
     * @var Akismet
     */
    protected $akismet;

    public function __construct(Akismet $akismet)
    {
        $this->akismet = $akismet;
    }

    public function handle(Saving $event)
    {
        $post = $event->post;

        if ($post->exists || $post->user->groups()->count()) {
            return;
        }

        $isSpam = $this->akismet->isSpam(
            $post->content,
            $post->user->username,
            $post->user->email,
            null,
            'comment'
        );

        if ($isSpam) {
            $post->is_approved = false;
            $post->is_spam = true;

            $post->afterSave(function ($post) {
                if ($post->number == 1) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }

                $flag = new Flag;

                $flag->post_id = $post->id;
                $flag->type = 'akismet';
                $flag->created_at = time();

                $flag->save();
            });
        }
    }
}
