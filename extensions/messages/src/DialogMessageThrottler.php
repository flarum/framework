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
 * A pause between messages, and caps on how many conversations one member can
 * open in an hour and how many people those reach, as a group reaches several
 * at once. The caps are what stop a fresh account working through the member
 * list, each message going out as an email from the forum's own address.
 */
class DialogMessageThrottler
{
    /** Seconds between one member's messages. */
    public static int $timeout = 10;

    /** New conversations one member may open in an hour. */
    public static int $newDialogsPerHour = 10;

    /**
     * People one member may reach in an hour through conversations they open.
     * A group reaches several at once. Never fewer than one message may go
     * to (RecipientLimit), so the largest group the forum allows can be
     * started.
     */
    public static int $newRecipientsPerHour = 20;

    public function __construct(
        protected RecipientLimit $recipientLimit
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== 'dialog-messages.create') {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if ($actor->can('dialog.sendMessageWithoutThrottle')) {
            return false;
        }

        // Leaving a group is no message of theirs to pause after.
        if (DialogMessage::where('user_id', $actor->id)
            ->where('type', '!=', DialogMessage::USER_LEFT)
            ->where('created_at', '>=', Carbon::now()->subSeconds(self::$timeout))
            ->exists()) {
            return true;
        }

        // A message with no dialog opens one, unless the pair already talk
        // directly. A group always opens one.
        $body = (array) $request->getParsedBody();
        $reaching = Arr::get($body, 'data.relationships.dialog.data.id') ? 0 : $this->recipientsOfNewDialog($actor->id, $body);

        if ($reaching) {
            // Conversations they opened themselves, by their first messages:
            // being added to others' doesn't use up a member's allowance.
            $opened = DialogMessage::query()
                ->where('user_id', $actor->id)
                ->where('number', 1)
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->pluck('dialog_id');

            if ($opened->count() >= self::$newDialogsPerHour) {
                return true;
            }

            $reached = UserDialogState::query()
                ->whereIn('dialog_id', $opened)
                ->where('user_id', '!=', $actor->id)
                ->count();

            // A message to more than the limit is refused anyway, saying so,
            // rather than throttled.
            $limit = $this->recipientLimit->get();

            if ($reached + min($reaching, $limit) > max(self::$newRecipientsPerHour, $limit)) {
                return true;
            }
        }

        return null;
    }

    /**
     * How many people a message with no dialog would open one with: none when
     * it continues the pair's direct conversation.
     */
    private function recipientsOfNewDialog(int $actorId, array $body): int
    {
        $recipients = array_unique(array_map('intval', array_filter(
            Arr::pluck(Arr::get($body, 'data.attributes.users', []), 'id'),
            fn (mixed $id) => $id && (int) $id !== $actorId
        )));

        // A group is always new.
        if (count($recipients) !== 1) {
            return count($recipients);
        }

        $continues = Dialog::query()
            ->where('type', Dialog::DIRECT)
            ->whereRelation('users', 'user_id', $actorId)
            ->whereRelation('users', 'user_id', reset($recipients))
            ->exists();

        return $continues ? 0 : 1;
    }
}
