<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Message;

use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Realtime\Websocket\TypingIdentity;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class Message
{
    public function __construct(protected stdClass $payload, protected ConnectionInterface $connection, protected Manager $manager)
    {
    }

    public function respond(): void
    {
        if (! $this->isAuthorizedClientEvent()) {
            return;
        }

        if (! $this->relayTyping()) {
            $channel = $this->manager->find($this->payload->channel);

            $channel->broadcastToEveryoneExcept(
                $this->payload,
                /** @phpstan-ignore-next-line */
                $this->connection->socketId
            );
        }

        $this->relayIndexTyping();
        $this->relayComposeTyping();
    }

    /**
     * The channel carrying the identities of users who are typing while hiding their
     * online status. Subscription requires `user.viewLastSeenAt` — see
     * {@link \Flarum\Realtime\Websocket\Api\DefaultChannels::typingIdentified()}.
     */
    public static function identifiedTypingChannel(int $discussionId): string
    {
        return "private-typingIdentified=$discussionId";
    }

    /**
     * Client-originated events are only permitted under the same rules Pusher
     * enforces, so a connection cannot forge events into channels it has no
     * business broadcasting to:
     *
     *   - the event name must be prefixed `client-`;
     *   - the target must be a private/presence channel (never a public one);
     *   - the channel must already exist and the sending connection must be
     *     subscribed to it.
     *
     * Without this, any connection holding the public app key could inject forged
     * events (e.g. spoofed notifications) into another user's private channel
     * without ever authorising a subscription.
     */
    protected function isAuthorizedClientEvent(): bool
    {
        $channelName = $this->payload->channel ?? null;
        $event = $this->payload->event ?? null;

        if (! is_string($channelName) || ! is_string($event)) {
            return false;
        }

        if (! Str::startsWith($event, 'client-')) {
            return false;
        }

        if (! Str::startsWith($channelName, ['private-', 'presence-'])) {
            return false;
        }

        $channel = $this->manager->find($channelName);

        return $channel !== null && $channel->hasConnection($this->connection);
    }

    /**
     * Relay a discussion typing event, disclosing the typist's identity to exactly
     * the audience entitled to it. Returns false for anything that isn't discussion
     * typing, so the caller falls back to the plain relay.
     *
     * Core treats `user.viewLastSeenAt` as the override for a user's `discloseOnline`
     * preference (see UserResource's `lastSeenAt` visibility). Typing is the one place
     * that override wasn't honoured, because the name was scrubbed at the sender and
     * so never reached anyone. It can't simply be scrubbed at the *receiver* either:
     * `private-typing={id}` is subscribable by everyone who can see the discussion, so
     * a name broadcast there is a name disclosed to all of them.
     *
     * Hence the split. When the typist is hiding:
     *
     *   - the full payload goes to the identified channel, which only holders of the
     *     permission can subscribe to;
     *   - an anonymised payload (no name at all) goes to the discussion channel,
     *     skipping the identified channel's subscribers so a privileged viewer sees
     *     one event rather than a name and an `[Anonymous]` for the same person.
     *
     * When the typist is disclosing, the single existing broadcast is unchanged. When
     * nobody privileged is listening, the identified channel doesn't exist and the
     * behaviour is exactly as before.
     *
     * Neither the name nor the preference is taken from the payload — both are looked
     * up from the identity the connection authenticated with, so a modified client
     * can't put words in another user's mouth (which it could, before this). An
     * unidentifiable sender fails closed to anonymous.
     */
    protected function relayTyping(): bool
    {
        if ($this->payload->event !== 'client-typing'
            || ! preg_match('/^private-typing=(\d+)$/', $this->payload->channel, $m)) {
            return false;
        }

        $discussionId = (int) $m[1];
        /** @phpstan-ignore-next-line */
        $sender = $this->connection->socketId;

        $userId = $this->manager->userIdForConnection($this->connection);
        $identity = $userId !== null ? resolve(TypingIdentity::class)->for($userId) : null;

        $channel = $this->manager->find($this->payload->channel);

        if ($identity !== null && $identity['discloseOnline']) {
            $channel->broadcastToEveryoneExcept(
                $this->typingPayload($this->payload->channel, $identity['displayName'], true),
                $sender
            );

            return true;
        }

        $identified = $this->manager->find(self::identifiedTypingChannel($discussionId));

        if ($identified && $identity !== null) {
            $identified->broadcastToEveryoneExcept(
                $this->typingPayload($identified->getName(), $identity['displayName'], false),
                $sender
            );
        }

        $channel->broadcastToEveryoneExcept(
            $this->typingPayload($this->payload->channel, null, false),
            array_merge([$sender], $identified ? $identified->socketIds() : [])
        );

        return true;
    }

    /**
     * Build a typing payload. The shape is unchanged, so existing `client-typing`
     * listeners (including the copy in flarum/messages, whose own channel this
     * doesn't touch) keep working; a null `displayName` is the anonymised form.
     *
     * `time` is passed through from the sender: it feeds the frontend's expiry
     * countdown against the *receiving* browser's clock, exactly as before.
     */
    protected function typingPayload(string $channel, ?string $displayName, bool $discloseOnline): stdClass
    {
        return (object) [
            'event' => 'client-typing',
            'channel' => $channel,
            'data' => [
                'displayName' => $displayName,
                'discloseOnline' => $discloseOnline,
                'time' => $this->payload->data->time ?? null,
            ],
        ];
    }

    /**
     * In addition to relaying the raw typing event to the discussion's own channel,
     * feed it into the coalesced index-typing presence so the discussion list can
     * show an ambient dot. See {@link IndexTypingPresence}.
     */
    protected function relayIndexTyping(): void
    {
        if ($this->payload->event !== 'client-typing'
            || ! preg_match('/^private-typing=(\d+)$/', $this->payload->channel, $m)) {
            return;
        }

        resolve(IndexTypingPresence::class)->touch((int) $m[1]);
    }

    /**
     * Feed compose-typing for a *new* discussion into the index-typing presence so
     * the tag list lights up before the discussion exists. There's no discussion id
     * yet, so the client sends the tag IDs it has selected, on its own
     * `private-user={id}` channel — the user id comes from the (already authorised)
     * channel name, never from the payload, and IndexTypingPresence re-authorises
     * each claimed tag against that user before surfacing it.
     */
    protected function relayComposeTyping(): void
    {
        if ($this->payload->event !== 'client-index-typing-tags'
            || ! preg_match('/^private-user=(\d+)$/', $this->payload->channel, $m)) {
            return;
        }

        $tags = $this->payload->data->tags ?? null;

        if (! is_array($tags)) {
            return;
        }

        resolve(IndexTypingPresence::class)->touchTags((int) $m[1], $tags);
    }
}
