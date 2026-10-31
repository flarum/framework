<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Flarum\Akismet\Akismet;
use Flarum\Approval\Event\PostWasApproved;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class SubmitHam
{
    public function __construct(
        protected Akismet $akismet,
        protected LoggerInterface $log
    ) {
    }

    public function handle(PostWasApproved $event): void
    {
        if (! $this->akismet->isConfigured()) {
            return;
        }

        $post = $event->post;

        if ($post->is_spam) {
            try {
                $this->akismet
                    ->withContent($post->content)
                    ->withIp($post->ip_address ?? '')
                    ->withAuthorName($post->user->username)
                    ->withAuthorEmail($post->user->email)
                    ->withType($post->number === 1 ? 'forum-post' : 'reply')
                    ->submitHam();
            } catch (GuzzleException $e) {
                // The feedback loop is best-effort — never let an Akismet
                // outage break the moderation action itself.
                $this->log->warning("[flarum/akismet] Failed to submit ham feedback: {$e->getMessage()}");
            }
        }
    }
}
