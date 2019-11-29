<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Post\Event\Hidden;
use TijsVerkoyen\Akismet\Akismet;

class SubmitSpam
{
    /**
     * @var Akismet
     */
    protected $akismet;

    public function __construct(Akismet $akismet)
    {
        $this->akismet = $akismet;
    }

    public function handle(Hidden $event)
    {
        $post = $event->post;

        if ($post->is_spam) {
            $this->akismet->submitSpam(
                $post->ip_address,
                null,
                $post->content,
                $post->user->username,
                $post->user->email
            );
        }
    }
}
