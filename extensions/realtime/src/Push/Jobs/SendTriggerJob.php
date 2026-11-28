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
use Flarum\Realtime\Push\OriginSocket;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SendTriggerJob extends Job
{
    /**
     * Declared with its default rather than promoted, so a job queued before
     * it existed, which unserialising leaves as it was, reads null.
     */
    private ?string $originSocket = null;

    public function __construct(
        private string $event,
        private AbstractModel $model,
        private ?User $actor = null,
        ?string $originSocket = null
    ) {
        parent::__construct();

        $this->originSocket = $originSocket ?? OriginSocket::current();
    }

    public function __invoke(Queue $queue): void
    {
        // Resolve a discussion for use with `visibleTo` permission checks. A post's
        // `discussion` is an Eloquent relation, not a declared property, so it has
        // to be read through the model.
        $discussion = $this->model instanceof Discussion
            ? $this->model
            : $this->model->getAttribute('discussion');

        if (! $discussion instanceof Discussion) {
            $discussion = null;
        }

        // First dispatch these messages to connected users, our code will literally do an internal
        // API requests and send the retrieved (user personalised) payload back to the client.
        // The socket ID came with a request, so it is only applied on the actor's own channel:
        // it must not be able to silence anyone else.
        $this->connectedUsers($discussion)
            ->filter(function (User $user) {
                return $user->exists
                    && $user->isGuest() === false
                    && ! ($user->is($this->actor) && $this->onlyOriginIsConnected($user));
            })
            ->each(function (User $recipient) use ($queue) {
                $queue->push(
                    new SendGeneratedPayloadJob(
                        $this->event,
                        $this->model,
                        $recipient,
                        exceptSocket: $recipient->is($this->actor) ? $this->originSocket : null
                    )
                );
            });

        // If public, push to everyone. Only discussion events: `public` reaches
        // every connected guest, so pushing every post event there as well
        // would multiply what the websocket server sends (see #5174).
        if ($this->model instanceof Discussion && $this->visibleTo($this->model)) {
            $queue->push(
                new SendGeneratedPayloadJob($this->event, $this->model)
            );
        }
    }

    /**
     * Building a payload costs internal API requests, wasted when the server
     * would only leave it out. The count alone doesn't say which tab is left:
     * the one that made the change may have closed since.
     */
    private function onlyOriginIsConnected(User $user): bool
    {
        if ($this->originSocket === null || ($this->subscriptionCounts[$user->id] ?? null) !== 1) {
            return false;
        }

        $channel = "private-user=$user->id";
        $response = $this->pusher()->getChannels(['filter_by_prefix' => $channel, 'info' => 'socket_ids']);

        return ($response->channels->{$channel}->socket_ids ?? null) === [$this->originSocket];
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
