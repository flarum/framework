<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Realtime\Push\Audience;
use Pusher\Pusher;

/**
 * A dialog message deleted for good, sent to the connected members who could
 * see it. Carries only IDs: the message, and perhaps its dialog, are gone, and
 * so are the memberships that would say who to tell.
 */
class SendDialogRemovalJob extends Job
{
    protected const CHANNELS_PER_TRIGGER = 100;

    /**
     * @param int[] $userIds Who could see the message before it was deleted.
     */
    public function __construct(
        protected string $event,
        protected int $id,
        protected int $dialogId,
        protected bool $dialogDeleted,
        protected array $userIds
    ) {
        parent::__construct();
    }

    public function handle(Pusher $pusher, Audience $audience): void
    {
        $connected = $audience->connectedUserIds();

        if (! $connected || ! $this->userIds) {
            return;
        }

        $userIds = array_values(array_intersect(array_unique($this->userIds), $connected));

        $payload = [
            'data' => ['type' => 'dialog-messages', 'id' => (string) $this->id],
            'meta' => ['dialogId' => (string) $this->dialogId, 'dialogDeleted' => $this->dialogDeleted],
        ];

        $channels = array_map(fn (int $id) => "private-user=$id", $userIds);

        foreach (array_chunk($channels, self::CHANNELS_PER_TRIGGER) as $chunk) {
            $pusher->trigger($chunk, $this->event, $payload);
        }
    }
}
