<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Realtime\Push\Audience;
use Flarum\Realtime\Push\RealtimeRegistry;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;
use Throwable;

/**
 * Payloads an extension addresses to particular users, worked out when the
 * job runs. The websocket server takes one event per request, so with several
 * users it is first asked who is connected.
 */
class SendUserPayloadsJob extends Job
{
    protected const CHANNELS_PER_TRIGGER = 100;

    /**
     * @param int $registration Index into RealtimeRegistry::getUserBroadcasts().
     */
    public function __construct(
        protected string $event,
        protected object $source,
        protected int $registration
    ) {
        parent::__construct();
    }

    public function handle(Pusher $pusher, Audience $audience, RealtimeRegistry $registry): void
    {
        $entry = $registry->getUserBroadcasts()[$this->registration] ?? null;

        // Registrations can shift when extensions are toggled while jobs wait.
        if (! $entry || ! in_array($this->event, $entry['events'], true)) {
            return;
        }

        $payloads = ($entry['getPayloads'])($this->source);

        if (count($payloads) > 1) {
            $connected = $audience->connectedUserIds();

            if ($connected === null) {
                return;
            }

            $payloads = array_intersect_key($payloads, array_flip($connected));
        }

        $groups = [];

        foreach ($payloads as $userId => $payload) {
            $key = json_encode($payload);
            $groups[$key]['payload'] = $payload;
            $groups[$key]['channels'][] = 'private-user='.(int) $userId;
        }

        foreach ($groups as $group) {
            foreach (array_chunk($group['channels'], self::CHANNELS_PER_TRIGGER) as $chunk) {
                try {
                    $pusher->trigger($chunk, $this->event, $group['payload']);
                } catch (Throwable $e) {
                    // Without a queue worker this runs inside the request that raised the event.
                    resolve(LoggerInterface::class)->warning('[realtime] Could not send a user payload: '.$e->getMessage());
                }
            }
        }
    }
}
