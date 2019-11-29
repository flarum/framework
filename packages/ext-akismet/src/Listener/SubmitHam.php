<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Approval\Event\PostWasApproved;
use TijsVerkoyen\Akismet\Akismet;

class SubmitHam
{
    /**
     * @var Akismet
     */
    protected $akismet;

    public function __construct(Akismet $akismet)
    {
        $this->akismet = $akismet;
    }

    public function handle(PostWasApproved $event)
    {
        $post = $event->post;

        if ($post->is_spam) {
            $this->akismet->submitHam(
                $post->ip_address,
                null,
                $post->content,
                $post->user->username,
                $post->user->email
            );
        }
    }
}
