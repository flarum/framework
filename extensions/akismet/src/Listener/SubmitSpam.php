<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Akismet\Akismet;
use Flarum\Post\Event\Hidden;

class SubmitSpam
{
    public function __construct(
        protected Akismet $akismet
    ) {
    }

    public function handle(Hidden $event): void
    {
        if (! $this->akismet->isConfigured()) {
            return;
        }

        $post = $event->post;

        if ($post->is_spam) {
            $this->akismet
                ->withContent($post->content)
                ->withIp($post->ip_address)
                ->withAuthorName($post->user->username)
                ->withAuthorEmail($post->user->email)
                ->withType($post->number === 1 ? 'forum-post' : 'reply')
                ->submitSpam();
        }
    }
}
