<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Database\AbstractModel;
use Flarum\Realtime\Push\Payload\Generator;
use Flarum\User\User;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Pusher\Pusher;

class SendGeneratedPayloadJob extends Job
{
    /**
     * The broadcast this delivery belongs to, when others of the same event
     * for the same model may be sent at the same time: see
     * SendDialogMessageJob. Declared with its default rather than promoted, so
     * a job queued before it existed, which unserialising leaves as it was,
     * reads null.
     */
    private ?string $dispatch = null;

    public function __construct(private string $event, private AbstractModel $model, private ?User $recipient = null, private ?array $includes = null, ?string $dispatch = null)
    {
        parent::__construct();

        $this->dispatch = $dispatch;
    }

    public function handle(Generator $generator, Pusher $pusher): void
    {
        $channel = $this->recipient
            ? "private-user={$this->recipient->id}"
            : 'public';

        $payload = $generator($this->model, $this->recipient, $this->includes);

        // Kill the job in case we cannot generate the payload.
        if (! $payload) {
            return;
        }

        $pusher->trigger(
            $channel,
            $this->event,
            $payload
        );
    }

    public function middleware(): array
    {
        $key = sprintf(
            '%s:%s:%s-%s:%s',
            $this->event,
            get_class($this),
            $this->model->getTable(),
            $this->model->getKey(),
            $this->recipient ? $this->recipient->getKey() : 'guest'
        ).($this->dispatch ? ':'.$this->dispatch : '');

        return [
            (new WithoutOverlapping($key))->dontRelease()
        ];
    }
}
