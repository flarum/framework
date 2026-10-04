import app from 'flarum/forum/app';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import Placeholder from 'flarum/common/components/Placeholder';
import humanTime from 'flarum/common/helpers/humanTime';
import classList from 'flarum/common/utils/classList';
import type Discussion from 'flarum/common/models/Discussion';
import type Model from 'flarum/common/Model';
import type Mithril from 'mithril';
import type { DeckColumnSource } from './DeckColumnType';

/** Matches flarum/realtime's TypingActivity channel and event. */
const CHANNEL = 'private-typing-activity';
const EVENT = 'typing-activity';

/** Same expiry as realtime's typing indicator: no ping for this long means they stopped. */
const ACTIVE_MS = 6000;
const HISTORY = 30;

interface TypingActivityData {
  userId: number | null;
  displayName: string | null;
  discussionId: number | null;
  tagIds: number[] | null;
}

interface Entry extends TypingActivityData {
  key: string;
  at: Date;
}

interface Channel {
  bind(event: string, callback: (data: TypingActivityData) => void): void;
  unbind(event: string, callback: (data: TypingActivityData) => void): void;
}

/**
 * Who is typing where, forum-wide, from flarum/realtime's typing-activity
 * channel. The server only sends each subscriber what they could see anyway
 * (discussions they can see, names of members hiding their online status only
 * with `user.viewLastSeenAt`), so this renders what arrives as it is.
 */
export default class TypingActivitySource implements DeckColumnSource {
  protected entries: Entry[] = [];
  protected socket: any = null;
  protected channel: Channel | null = null;
  protected requested = new Set<number>();
  protected expiryTimer: number | null = null;

  load(): Promise<unknown> {
    return Promise.resolve();
  }

  /** Subscribes, or re-subscribes after realtime has replaced its socket. */
  start(): void {
    const socket = (app as any).websocket;

    if (!socket || socket === this.socket) return;

    this.stop();
    this.socket = socket;
    this.channel = socket.subscribe(CHANNEL) as Channel;
    this.channel.bind(EVENT, this.onActivity);
  }

  stop(): void {
    this.channel?.unbind(EVENT, this.onActivity);
    this.socket?.unsubscribe(CHANNEL);
    this.socket = this.channel = null;

    if (this.expiryTimer) clearTimeout(this.expiryTimer);
    this.expiryTimer = null;
  }

  /** Fed by its own channel; nothing else concerns it. */
  onRealtime(): void {}

  checkForNew(): Promise<number> {
    return Promise.resolve(0);
  }

  showNew(): Promise<unknown> {
    return Promise.resolve();
  }

  view(): Mithril.Children {
    if (!this.socket) {
      return <Placeholder text={app.translator.trans('flarum-deck.forum.typing.offline_text')} />;
    }

    if (!this.entries.length) {
      return <Placeholder text={app.translator.trans('flarum-deck.forum.typing.empty_text')} />;
    }

    const now = Date.now();

    return (
      <ul className="DeckTyping">
        {this.entries.map((entry) => {
          const active = now - entry.at.getTime() < ACTIVE_MS;

          return (
            <li key={entry.key} className={classList('DeckTyping-item', { 'DeckTyping-item--active': active })}>
              <Icon name="fas fa-keyboard" className="DeckTyping-icon" />
              <div className="DeckTyping-text">
                {this.describe(entry, active)}
                {!active && <span className="DeckTyping-time">{humanTime(entry.at)}</span>}
              </div>
            </li>
          );
        })}
      </ul>
    );
  }

  protected describe(entry: Entry, active: boolean): Mithril.Children {
    const user = entry.userId ? app.store.getById<Model & { username(): string }>('users', String(entry.userId)) : null;
    const nameText = entry.displayName ?? app.translator.trans('flarum-deck.forum.typing.someone');
    const name = <strong>{user ? <Link href={app.route('user', { username: user.username() })}>{nameText}</Link> : nameText}</strong>;

    if (entry.discussionId) {
      const discussion = app.store.getById<Discussion>('discussions', String(entry.discussionId));
      const link = discussion ? <Link href={app.route.discussion(discussion)}>{discussion.title()}</Link> : '…';

      return active
        ? app.translator.trans('flarum-deck.forum.typing.replying_text', { name, discussion: link })
        : app.translator.trans('flarum-deck.forum.typing.was_replying_text', { name, discussion: link });
    }

    const tags = (entry.tagIds ?? [])
      .map((id) => app.store.getById<Model & { name(): string }>('tags', String(id))?.name())
      .filter(Boolean)
      .join(', ');

    if (!tags) {
      return active
        ? app.translator.trans('flarum-deck.forum.typing.starting_text', { name })
        : app.translator.trans('flarum-deck.forum.typing.was_starting_text', { name });
    }

    return active
      ? app.translator.trans('flarum-deck.forum.typing.starting_in_text', { name, tags })
      : app.translator.trans('flarum-deck.forum.typing.was_starting_in_text', { name, tags });
  }

  protected onActivity = (data: TypingActivityData): void => {
    const key = `${data.userId ?? 'someone'}:${data.discussionId ?? 'new'}`;

    this.entries = [{ ...data, key, at: new Date() }, ...this.entries.filter((entry) => entry.key !== key)].slice(0, HISTORY);

    if (data.discussionId && !this.requested.has(data.discussionId)) {
      this.requested.add(data.discussionId);

      if (!app.store.getById('discussions', String(data.discussionId))) {
        app.store
          .find<Discussion>('discussions', String(data.discussionId))
          .then(() => m.redraw())
          .catch(() => {});
      }
    }

    // Redraw once more after it lapses, so "is replying" becomes "was replying".
    if (this.expiryTimer) clearTimeout(this.expiryTimer);
    this.expiryTimer = window.setTimeout(() => m.redraw(), ACTIVE_MS + 100);

    m.redraw();
  };
}
