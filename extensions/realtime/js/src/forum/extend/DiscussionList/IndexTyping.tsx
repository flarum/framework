import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Icon from 'flarum/common/components/Icon';
import RealtimeState from '../../RealtimeState';

/**
 * Ambient "someone is typing here" dot on discussion list rows.
 *
 * Presence comes from the single shared `index-typing` channel (see Application),
 * so there are no per-discussion subscriptions — this extender only *reads* the
 * shared IndexTypingState.
 *
 * DiscussionListItem retains its subtree (onbeforeupdate → SubtreeRetainer), so a
 * bare m.redraw() does NOT re-run infoItems. We register a SubtreeRetainer check
 * that tracks this discussion's typing flag (combined with the in-view flag) so
 * the row re-renders precisely when its dot should appear or disappear.
 *
 * To keep redraw work bounded on long lists, a row only consults the typing state
 * while it's actually in the viewport (tracked with an IntersectionObserver), so
 * off-screen rows never schedule the self-clearing redraw timers isTyping() sets up.
 */
export default function (): void {
  extend('flarum/forum/components/DiscussionListItem', 'oninit', function (this: any) {
    // Default to in-view; the observer (set up in oncreate) corrects this once it
    // reports. Avoids a row that's already on screen at load missing its first dot
    // before the observer's initial async callback fires.
    this._realtimeInView = true;

    const isTyping = (): boolean => {
      const discussion = this.attrs.discussion;
      return !!discussion && this._realtimeInView && RealtimeState.indexTyping.isTyping(discussion.id());
    };

    // Re-render this row when its typing flag flips, despite subtree retention.
    this.subtree.check(isTyping);
  });

  extend('flarum/forum/components/DiscussionListItem', 'oncreate', function (this: any, _val, vnode: any) {
    const el = vnode.dom as HTMLElement;

    this._realtimeObserver = new IntersectionObserver((entries) => {
      const nowInView = entries[0]?.isIntersecting ?? false;
      if (nowInView !== this._realtimeInView) {
        this._realtimeInView = nowInView;
        m.redraw();
      }
    });
    this._realtimeObserver.observe(el);
  });

  extend('flarum/forum/components/DiscussionListItem', 'onremove', function (this: any) {
    this._realtimeObserver?.disconnect();
    this._realtimeObserver = null;
  });

  extend('flarum/forum/components/DiscussionListItem', 'infoItems', function (this: any, items) {
    if (!this._realtimeInView) return;

    const discussion = this.attrs.discussion;
    if (!discussion || !RealtimeState.indexTyping.isTyping(discussion.id())) return;

    items.add(
      'realtimeTyping',
      <span className="DiscussionListItem-typing" aria-label={app.translator.trans('flarum-realtime.forum.index-typing.someone-typing')}>
        <Icon name="fas fa-ellipsis-h fa-beat" className="DiscussionListItem-typing-icon" />
      </span>,
      // Sit just after the tag label (flarum-tags adds 'tags' at priority 10) and
      // before the "X replied/started" terminal post, so the tag stays the leading
      // element of the info row — important on mobile where it's the first thing shown.
      5
    );
  });
}
