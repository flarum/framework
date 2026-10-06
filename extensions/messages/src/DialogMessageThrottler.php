<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rate limits sending, the way core's PostCreationThrottler limits posting.
 * Two limits: a pause between messages, and a cap on how many conversations
 * one member can open in an hour. The second is what stops a fresh account
 * working through the member list, each message going out as an email from
 * the forum's own address.
 */
class DialogMessageThrottler
{
    /** Seconds between one member's messages. */
    public static int $timeout = 10;

    /** New conversations one member may open in an hour. */
    public static int $newDialogsPerHour = 10;

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== 'dialog-messages.create') {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->can('dialog.sendMessageWithoutThrottle')) {
            return false;
        }

        if (DialogMessage::where('user_id', $actor->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))
            ->exists()) {
            return true;
        }

        // A message with no dialog opens one, unless the pair already talk.
        $body = (array) $request->getParsedBody();

        if (! Arr::get($body, 'data.relationships.dialog.data.id') && $this->opensDialog($actor->id, $body)) {
            $opened = Dialog::whereRelation('users', 'user_id', $actor->id)
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->count();

            if ($opened >= self::$newDialogsPerHour) {
                return true;
            }
        }

        return null;
    }

    private function opensDialog(int $actorId, array $body): bool
    {
        $recipients = array_filter(
            Arr::pluck(Arr::get($body, 'data.attributes.users', []), 'id'),
            fn (mixed $id) => $id && (int) $id !== $actorId
        );

        if (! $recipients) {
            return false;
        }

        return ! Dialog::whereRelation('users', 'user_id', $actorId)
            ->whereRelation('users', 'user_id', (int) array_values($recipients)[0])
            ->exists();
    }
}
