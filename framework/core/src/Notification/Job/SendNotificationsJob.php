<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendNotificationsJob extends AbstractJob
{
    public function __construct(
        private readonly BlueprintInterface&AlertableInterface $blueprint,
        /** @var User[] */
        private readonly array $recipients = []
    ) {
    }

    public function handle(): void
    {
        // Race guard for #4622: NotificationSyncer::sync() reads matchingBlueprint
        // and decides who's a "new" recipient *before* the actual INSERT happens
        // (here). If sync() is called twice in rapid succession (e.g. Posted
        // followed quickly by Revised) before either job runs, both reads see no
        // row and both jobs queue the same recipients. Re-run the dedup check at
        // INSERT time so only the first job actually inserts; later jobs no-op.
        $alreadyInserted = Notification::matchingBlueprint($this->blueprint)
            ->whereIn('user_id', array_map(fn (User $user) => $user->id, $this->recipients))
            ->pluck('user_id')
            ->all();

        $newRecipients = array_filter(
            $this->recipients,
            fn (User $user) => ! in_array($user->id, $alreadyInserted, true)
        );

        if (empty($newRecipients)) {
            return;
        }

        Notification::notify($newRecipients, $this->blueprint);
    }
}
