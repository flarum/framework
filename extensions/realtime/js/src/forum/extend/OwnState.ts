import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Application from 'flarum/common/Application';
import NotificationListState from 'flarum/forum/states/NotificationListState';
import type Notification from 'flarum/common/models/Notification';
import type Discussion from 'flarum/common/models/Discussion';
import RealtimeState from '../RealtimeState';

const NOTIFICATIONS = 'ownNotifications';
const DISCUSSION_READ = 'ownDiscussionRead';
const ALL_DISCUSSIONS_READ = 'ownAllDiscussionsRead';

export interface NotificationsPayload {
  read?: { id: string; type: string; subjectId: string };
  /** When everything up to then was read or deleted. */
  allRead?: string;
  allDeleted?: string;
  unreadNotificationCount?: number;
  newNotificationCount?: number;
}

let notificationListStale = false;

/**
 * Core only reloads the list while there are new notifications, and pushed ones
 * never join its pages. Not refetched now: that request would push to the
 * user's other tabs, and theirs back to this one. Nor cleared, which would
 * blank a list that is open.
 */
function forgetNotificationList(): void {
  notificationListStale = true;
}

export function applyNotifications(data: NotificationsPayload): void {
  const user = app.session.user;
  if (!user) return;

  const counts: Record<string, number> = {};
  if (data.unreadNotificationCount !== undefined) counts.unreadNotificationCount = data.unreadNotificationCount;
  if (data.newNotificationCount !== undefined) counts.newNotificationCount = data.newNotificationCount;
  user.pushAttributes(counts);

  if (data.allDeleted || (data.newNotificationCount === 0 && !data.read && !data.allRead)) {
    forgetNotificationList();
  } else if (data.allRead || data.read) {
    const cutoff = data.allRead ? new Date(data.allRead).getTime() : null;
    const read = data.read;

    // Reading one marks every notification of its type about the same subject.
    const matches = (notification: Notification): boolean =>
      cutoff !== null
        ? (notification.createdAt()?.getTime() ?? 0) <= cutoff
        : notification.id() === read!.id ||
          (notification.contentType() === read!.type && (notification.data.relationships as any)?.subject?.data?.id === read!.subjectId);

    app.store
      .all<Notification>('notifications')
      .filter(matches)
      .forEach((notification) => notification.pushAttributes({ isRead: true }));
  }

  m.redraw();
}

export function applyDiscussionRead(data: { id: string; lastReadPostNumber: number; lastReadAt: string | null }): void {
  const discussion = app.store.getById<Discussion>('discussions', data.id);

  if (!discussion || (discussion.lastReadPostNumber() ?? 0) >= data.lastReadPostNumber) return;

  discussion.pushAttributes({ lastReadPostNumber: data.lastReadPostNumber, lastReadAt: data.lastReadAt });
  m.redraw();
}

export function applyAllDiscussionsRead(data: { markedAllAsReadAt: string }): void {
  const user = app.session.user;
  const current = user?.markedAllAsReadAt()?.getTime() ?? 0;

  // A retried job can arrive after a newer one.
  if (!user || new Date(data.markedAllAsReadAt).getTime() <= current) return;

  user.pushAttributes({ markedAllAsReadAt: data.markedAllAsReadAt });
  m.redraw();
}

/**
 * Pushes missed while disconnected are not replayed.
 */
export function catchUpOwnState(): void {
  const user = app.session.user;
  if (!user) return;

  forgetNotificationList();

  // Spread out: a daemon restart reconnects every tab at once.
  setTimeout(() => {
    app.store
      .find('users', user.id()!, {}, { errorHandler: () => {} })
      .then(() => m.redraw())
      .catch(() => {});
  }, Math.random() * 5000);
}

export default function OwnState(): void {
  // Lets the server leave this tab out of pushes about its own requests.
  extend(Application.prototype, 'transformRequestOptions' as any, function (options: any) {
    const connection = app.websocket?.connection;
    // pusher-js keeps the last socket ID while it reconnects.
    const socketId = connection?.state === 'connected' ? connection.socket_id : null;
    const apiUrl = app.forum?.attribute<string>('apiUrl');

    if (!socketId || !apiUrl || !String(options.url ?? '').startsWith(apiUrl)) return;

    extend(options, 'config', (_: unknown, xhr: unknown) => {
      (xhr as XMLHttpRequest).setRequestHeader('X-Socket-ID', socketId);
    });
  });

  override(NotificationListState.prototype, 'load', function (this: any, original: () => Promise<void>) {
    if (notificationListStale) {
      notificationListStale = false;
      this.pages = [];
      this.location = { page: 1 };
    }

    return original();
  });

  RealtimeState.onUserChannelReady((channel: any) => {
    channel.bind(NOTIFICATIONS, applyNotifications);
    channel.bind(DISCUSSION_READ, applyDiscussionRead);
    channel.bind(ALL_DISCUSSIONS_READ, applyAllDiscussionsRead);
  });
}
