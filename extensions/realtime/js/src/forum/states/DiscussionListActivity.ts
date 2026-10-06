import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import type { Channel } from 'pusher-js';
import WebsocketUpdates from '../extend/DiscussionList/WebsocketUpdates';

/** Events that put a discussion back at the top of the list. */
export const LIST_ACTIVITY_EVENTS = ['Flarum\\Discussion\\Event\\Started', 'Flarum\\Post\\Event\\Posted', 'discussionRestored'];

/**
 * New activity for the forum's discussion list (`app.discussions`), collected
 * for the whole session rather than only while the list is on screen. Activity
 * that arrives while the reader is on a discussion is still here when they
 * come back to the list, which keeps its loaded pages and scroll position
 * instead of reloading.
 */
export default class DiscussionListActivity {
  readonly updates = new WebsocketUpdates();

  /**
   * The route the list was last shown under. Filtering needs it while another
   * page is open, when `app.current` is that page.
   */
  protected listRoute: string | null = null;

  protected listShown = false;

  protected readonly onActivity = (data: unknown): void => this.receive(data);

  /**
   * Safe to call again with the same channel, or with the new channels a
   * reconnect creates: the handler is never bound to a channel twice.
   */
  bind(channel: Channel): void {
    for (const event of LIST_ACTIVITY_EVENTS) {
      channel.unbind(event, this.onActivity);
      channel.bind(event, this.onActivity);
    }
  }

  /**
   * The list is about to render. With auto-release on, what arrived while the
   * reader was away goes in before the first render, so the list is current
   * when it appears. With manual release, it waits behind the button.
   */
  showList(routeName: string | null): void {
    this.listRoute = routeName;
    this.listShown = true;

    if (this.updates.isEmpty()) return;

    if (this.updates.autoRelease()) {
      this.release();
    } else {
      app.setTitleCount(this.updates.length());
    }
  }

  hideList(): void {
    this.listShown = false;

    // The countdown is only meaningful while the button is visible. What's
    // left over is released when the reader comes back.
    this.updates.stopTimer();
  }

  /**
   * The list was reloaded, so it already shows everything collected so far.
   */
  listReloaded(): void {
    this.updates.reset();
  }

  release(): void {
    this.updates.release(app.discussions);
  }

  receive(data: unknown): void {
    const params = (app as any).discussions.getParams();
    const activeTag: any = params.tags ? (app.store as any).getBy('tags', 'slug', params.tags) : null;
    const noFilters: boolean = Object.keys(params.filter ?? {}).length === 0;

    if (params.q || params.sort || !(activeTag || noFilters)) return;

    const entity = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

    let discussion: Discussion | null = entity instanceof Discussion ? entity : null;

    if (!discussion && entity instanceof Post) {
      discussion = (entity as any).discussion();
    }

    if (!discussion) return;

    // Byobu private discussions guards.
    if (this.listRoute === 'byobuPrivate' && !((discussion as any).recipientUsers?.() && (discussion as any).recipientGroups?.())) {
      return;
    }

    if (
      this.listRoute === 'byobuPrivate' &&
      (discussion as any).recipientUsers?.()?.length === 0 &&
      (discussion as any).recipientGroups?.()?.length === 0
    ) {
      return;
    }

    if (this.listRoute === 'byobuUserPrivate') return;

    // Tag-based filtering (flarum/tags).
    if (activeTag && (discussion as any).tags?.()) {
      const tagIds: string[] = (discussion as any).tags().map((tag: any): string => tag.id());
      if (!tagIds.includes(activeTag.id())) return;
    }

    if (
      (discussion as any).tags?.() &&
      (discussion as any).tags().find((tag: any) => {
        if (activeTag && activeTag.id() === tag.id()) return false;
        if (!activeTag && tag.isHidden?.()) return true;
        return tag.subscription?.() === 'hide';
      })
    ) {
      return;
    }

    // Subscription filtering (flarum/subscriptions).
    if ((discussion as any).subscription?.() === 'ignore') return;

    const subscribedTag = (discussion as any).tags?.()?.find((tag: any): boolean => {
      return tag.subscription?.() === 'lurk' || tag.subscription?.() === 'follow';
    });

    if (this.listRoute === 'following') {
      if ((params.filter?.['following-tag'] && !subscribedTag) || (discussion as any).subscription?.() !== 'follow') {
        return;
      }
    }

    if (this.updates.has(discussion)) return;

    // Already on top, and nothing waiting would be released above it. If
    // something is waiting, this one has to go with it, or the older activity
    // would end up on top.
    if (this.updates.isEmpty() && (app as any).discussions.getPages()[0]?.items[0]?.id() === discussion.id()) return;

    this.updates.push(discussion);

    // Away from the list, nothing shows the count, and the page on screen
    // owns the title.
    if (this.listShown) {
      app.setTitleCount(this.updates.length());
      m.redraw();
    }
  }
}
