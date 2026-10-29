<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Frontend\Compiler\AssetsRevision;

/**
 * Pushes the current asset revision token to every connected client, so a browsing
 * user is told to reload when the forum's JS/CSS has been recompiled — without
 * waiting for their next interaction.
 *
 * Broadcasts to the public channel (guests) and every connected `private-user=*`
 * channel (logged-in users), since there is no single channel all clients share.
 */
class BroadcastAssetsRevisionJob extends Job
{
    public const EVENT = 'assetsRevision';

    public function __invoke(AssetsRevision $revision): void
    {
        $pusher = $this->pusher();

        $payload = ['revision' => $revision->token()];

        // Guests.
        $pusher->trigger('public', self::EVENT, $payload);

        // Logged-in users: one channel per connected user.
        $response = $pusher->getChannels(['filter_by_prefix' => 'private-user=']);

        $channels = array_keys((array) ($response->channels ?? []));

        // Pusher accepts up to 100 channels per trigger call.
        foreach (array_chunk($channels, 100) as $batch) {
            $pusher->trigger($batch, self::EVENT, $payload);
        }
    }
}
