<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Database\AbstractModel;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SendDialogMessageJob extends Job
{
    public function __construct(
        private string $event,
        private AbstractModel $model
    ) {
        parent::__construct();
    }

    public function __invoke(Queue $queue): void
    {
        /** @var DialogMessage $message */
        $message = $this->model;

        $this->getConnectedDialogUsers($message->dialog)
            ->each(function (User $recipient) use ($queue) {
                $queue->push(
                    new SendGeneratedPayloadJob($this->event, $this->model, $recipient)
                );
            });
    }

    /**
     * @param Dialog $dialog
     * @return Collection&iterable<User>
     * @throws \Pusher\PusherException
     */
    private function getConnectedDialogUsers(Dialog $dialog): Collection
    {
        $response = $this->pusher()->getChannels([
            'filter_by_prefix' => 'private-user='
        ]);

        $users = Collection::make();

        /** @phpstan-ignore-next-line */
        if (! $response) {
            return $users;
        }

        foreach ($response->channels as $name => $channel) {
            $users->put($name, Str::after($name, 'private-user='));
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::query()->find($users->unique()->values());

        // Check user in private message list
        /** @var Collection&iterable<User> */
        return $users->filter(function (User $user) use ($dialog) {
            return $dialog->users->contains($user);
        })->values();
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
