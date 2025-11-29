<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SendTriggerJob extends Job
{
    public function __construct(
        private string $event,
        private AbstractModel $model,
        private ?User $actor = null
    ) {
        parent::__construct();
    }

    public function __invoke(Queue $queue): void
    {
        // Resolve a discussion for use with `visibleTo` permission checks.
        if ($this->model instanceof Discussion) {
            $discussion = $this->model;
        } elseif (property_exists($this->model, 'discussion')) {
            $discussion = $this->model->discussion ?? null;
        } else {
            $discussion = null;
        }

        // First dispatch these messages to connected users, our code will literally do an internal
        // API requests and send the retrieved (user personalised) payload back to the client.
        $this->connectedUsers($discussion)
            ->filter(function (User $user) {
                return $user->exists
                    && $user->isGuest() === false
                    && $user->is($this->actor) === false;
            })
            ->each(function (User $recipient) use ($queue) {
                $queue->push(
                    new SendGeneratedPayloadJob($this->event, $this->model, $recipient)
                );
            });

        // If public, push to everyone.
        if ($discussion && $this->visibleTo($discussion)) {
            $queue->push(
                new SendGeneratedPayloadJob($this->event, $this->model)
            );
        }
    }

    public function middleware(): array
    {
        $key = sprintf(
            '%s:%s.%s',
            $this->event,
            $this->model->getTable(),
            $this->model->getKey()
        );

        return [
            (new WithoutOverlapping($key))->dontRelease()
        ];
    }
}
