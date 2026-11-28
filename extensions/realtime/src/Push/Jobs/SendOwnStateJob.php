<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Realtime\Push\OriginSocket;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;
use Throwable;

class SendOwnStateJob extends Job
{
    private ?string $originSocket = null;

    public function __construct(
        private int $userId,
        private string $event,
        private array $data,
        private bool $withNotificationCounts = false,
        ?string $originSocket = null
    ) {
        parent::__construct();

        $this->originSocket = $originSocket ?? OriginSocket::current();
    }

    /**
     * While set, pushes are skipped rather than each waiting out the client's
     * retries against a server that is down, on every scroll of a discussion.
     */
    public const UNREACHABLE_KEY = 'flarum-realtime.own-state-unreachable';

    public function handle(Pusher $pusher, Repository $cache): void
    {
        if ($cache->has(self::UNREACHABLE_KEY)) {
            return;
        }

        $data = $this->data;

        // Counted when the job runs, so a queue delay can't send stale ones.
        if ($this->withNotificationCounts) {
            if (! $user = User::query()->find($this->userId)) {
                return;
            }

            $data['unreadNotificationCount'] = $user->getUnreadNotificationCount();
            $data['newNotificationCount'] = $user->getNewNotificationCount();
        }

        try {
            $pusher->trigger(
                "private-user={$this->userId}",
                $this->event,
                $data,
                $this->originSocket ? ['socket_id' => $this->originSocket] : []
            );
        } catch (Throwable $e) {
            // On the sync queue this runs inside the request: a push must not fail what the user did.
            $cache->put(self::UNREACHABLE_KEY, true, 30);
            resolve(LoggerInterface::class)->warning('[realtime] Could not send own state: '.$e->getMessage());
        }
    }
}
