import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Icon from 'flarum/common/components/Icon';
import RealtimeState from '../../RealtimeState';

/**
 * Ambient "someone is typing here" dot on the index sidebar tag list.
 *
 * Presence comes from the same `index-typing` channels as the discussion-list
 * dot (see Application), which now carry the tags a typing discussion belongs to.
 * This extender only *reads* the shared IndexTagTypingState — there are no
 * per-tag subscriptions beyond the ones realtime already makes, and the backend
 * scopes the tag IDs per channel so a restricted tag is never surfaced to an
 * audience that can't see it.
 *
 * The sidebar tag list is short and always on screen, so (unlike the
 * discussion-list dot) it needs no IntersectionObserver or SubtreeRetainer:
 * IndexTagTypingState.set()/isTyping() drive m.redraw() directly, including the
 * self-clearing redraw when the dot should disappear.
 *
 * Only loaded when flarum-tags is active (see forum/index.ts).
 */
export default function (): void {
  extend('ext:flarum/tags/forum/components/TagLinkButton', 'linkItems', function (this: any, items: any) {
    const tag = this.attrs.model;

    if (!tag || !RealtimeState.indexTagTyping.isTyping(tag.id())) {
      return;
    }

    // Sit after the tag label (label is priority 90), trailing the link.
    items.add(
      'realtimeTyping',
      <span className="TagLinkButton-typing" aria-label={app.translator.trans('flarum-realtime.forum.index-typing.someone-typing-tag')}>
        <Icon name="fas fa-ellipsis-h fa-beat" className="TagLinkButton-typing-icon" />
      </span>,
      0
    );
  });

  // Surface a dot on the relevant tags while someone is composing a NEW discussion
  // in them — before the discussion (and its id) exists. The reply path can't cover
  // this because it keys on a discussion id; here the client tells the server which
  // tags it has selected (server-side re-authorised against the actor, so a restricted
  // tag can't be spoofed). Fires on the user's own private channel.
  extend('flarum/forum/components/DiscussionComposer', 'oninit', function (this: any) {
    let previousContent: string | null = null;
    let lastSentAt = 0;

    // Send a ping whenever the content changes since the last poll, but never more
    // than once per SEND_INTERVAL. Crucially we DON'T track lodash throttle edges
    // here: the gate is "content changed AND it's been long enough", so a resume
    // after a pause pings on the very next poll — the server's idle/active state and
    // the dot's self-clearing TTL would otherwise drift apart and the dot wouldn't
    // reliably come back. Re-pinging well within the 6s TTL keeps the dot alive.
    const SEND_INTERVAL = 2000;

    const checkTyping = (): void => {
      const tags: any[] = this.composer?.fields?.tags || [];
      const content: string = this.composer?.fields?.content?.() || '';

      // Only signal once there's something being written and at least one tag chosen.
      if (!content.length || !tags.length) {
        previousContent = content;
        return;
      }

      const changed = content !== previousContent;
      previousContent = content;

      const now = Date.now();
      if (!changed || now - lastSentAt < SEND_INTERVAL) return;
      lastSentAt = now;

      app.websocket_channels?.user?.trigger('client-index-typing-tags', {
        tags: tags.map((tag) => Number(tag.id())),
      });
    };

    this._composeTypingListener = setInterval(checkTyping, 1000);
  });

  extend('flarum/forum/components/DiscussionComposer', 'onremove', function (this: any) {
    if (this._composeTypingListener) {
      clearInterval(this._composeTypingListener);
      this._composeTypingListener = null;
    }
  });
}
