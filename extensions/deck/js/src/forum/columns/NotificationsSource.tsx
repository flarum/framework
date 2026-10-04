import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import NotificationList from 'flarum/forum/components/NotificationList';
import type Notification from 'flarum/common/models/Notification';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
import { NOTIFICATION } from './realtimeEvents';

/**
 * Shares `app.notifications` with the header dropdown, so reading a
 * notification in one place updates the other.
 */
export default class NotificationsSource implements DeckColumnSource {
  load(): Promise<unknown> {
    return app.notifications.load();
  }

  view() {
    return <NotificationList state={app.notifications} />;
  }

  async checkForNew(): Promise<number> {
    return (await this.unshown()).filter((notification) => !notification.isRead()).length;
  }

  async applyNew(): Promise<number | null> {
    const firstPage = app.notifications.getPages()[0];

    if (!firstPage) return null;

    const unshown = await this.unshown();
    firstPage.items.unshift(...unshown);

    return unshown.length;
  }

  /** Per-member and indexed by user, so the newest few are a cheap question. */
  protected async unshown(): Promise<Notification[]> {
    const latest = await app.store.find<Notification[]>('notifications', { page: { limit: 10, total: 0 } });
    const shown = new Set(
      app.notifications
        .getPages()
        .flatMap((page) => page.items)
        .map((notification) => notification.id())
    );

    return latest.filter((notification) => !shown.has(notification.id()));
  }

  /** Realtime sends each new notification on the member's channel: shown at once, no request. */
  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    if (event.name !== NOTIFICATION || event.model?.data?.type !== 'notifications') return;

    const firstPage = app.notifications.getPages()[0];
    const notification = event.model as Notification;

    if (!firstPage || firstPage.items.some((item) => item.id() === notification.id())) return;

    firstPage.items.unshift(notification);

    return 'inserted';
  }

  showNew(): Promise<unknown> {
    return app.notifications.revalidate();
  }

  controls(items: ItemList<Mithril.Children>) {
    items.add(
      'markAllAsRead',
      <Button icon="fas fa-check" onclick={() => app.notifications.markAllAsRead()}>
        {app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip')}
      </Button>
    );
  }
}
