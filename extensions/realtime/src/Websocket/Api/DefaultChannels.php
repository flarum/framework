<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

/**
 * How realtime's own channels are authorized.
 *
 * These used to be methods on {@link AuthController}, found by name with
 * `method_exists()`. They are registered into the {@link ChannelRegistry} from the
 * extension's `extend.php` now, through the same extender an extension would use —
 * so the API is exercised by its first consumer rather than sitting beside a
 * private path only realtime can take. The extender resolves this class through
 * the container, so its dependencies are injected as usual.
 */
class DefaultChannels
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * A user's own channel — their notifications, and the identity claim the
     * websocket server reads a connection's user id from
     * ({@link \Flarum\Realtime\Websocket\Channel\Manager::userIdForConnection()}).
     * Signing it for anyone else would hand them both.
     */
    public function user(User $actor, int $id): bool
    {
        return ! $actor->isGuest() && $actor->id === $id;
    }

    /**
     * Who is typing in a discussion. Audience: everyone who can see the discussion,
     * guests included.
     */
    public function typing(User $actor, int $id): bool
    {
        return Discussion::whereVisibleTo($actor)->where('id', $id)->exists();
    }

    public function privateMessageTyping(User $actor, int $id): bool
    {
        return \Flarum\Messages\Dialog::whereVisibleTo($actor)->where('id', $id)->exists();
    }

    /**
     * Authorize the channel naming members who type in a private conversation while
     * hiding their online status: the conversation's members who hold core's
     * `user.viewLastSeenAt`, the override for that preference. See
     * {@link \Flarum\Realtime\Websocket\Message\Message::relayDialogTyping()}.
     */
    public function privateMessageTypingIdentified(User $actor, int $id): bool
    {
        return $actor->hasPermission('user.viewLastSeenAt')
            && $this->privateMessageTyping($actor, $id);
    }

    /**
     * Authorize the channel that discloses who is typing while hiding their online
     * status. `user.viewLastSeenAt` is core's override for the `discloseOnline`
     * preference, so it gates this too — plus the ordinary requirements for seeing
     * the typing indicator at all, since this channel carries nothing else.
     *
     * Doing it here means the permission is evaluated once per subscription, against
     * a real actor in a normal request, rather than per event inside the websocket
     * server. See {@link \Flarum\Realtime\Websocket\Message\Message::relayTyping()}.
     */
    public function typingIdentified(User $actor, int $id): bool
    {
        if (! $this->settings->get('flarum-realtime.typing-indicator')
            || ! $actor->hasPermission('user.viewLastSeenAt')) {
            return false;
        }

        $discussion = Discussion::whereVisibleTo($actor)->find($id);

        return $discussion !== null
            && $actor->can('flarum-realtime.view-who-types', $discussion);
    }

    /**
     * Authorize the forum-wide typing feed (`private-typing-activity`, no id). What
     * each subscriber then receives is narrowed further, per event, by
     * {@link \Flarum\Realtime\Websocket\TypingActivity}.
     */
    public function typingActivity(User $actor): bool
    {
        return ! $actor->isGuest()
            && $this->settings->get('flarum-realtime.typing-indicator')
            && $actor->hasPermission('flarum-realtime.view-all-typing');
    }

    /**
     * Authorize a restricted-tag index-typing channel: the actor may listen iff
     * they can see the tag. Reaching this without flarum-tags active is rejected.
     */
    public function indexTypingTag(User $actor, int $id): bool
    {
        if (! class_exists(\Flarum\Tags\Tag::class)) {
            return false;
        }

        return \Flarum\Tags\Tag::whereVisibleTo($actor)->where('id', $id)->exists();
    }

    /**
     * The forum-wide `presence-online` roster (no id).
     *
     * @return array<string, mixed>
     */
    public function online(User $actor): array
    {
        return [
            'displayName' => $actor->display_name,
        ];
    }
}
