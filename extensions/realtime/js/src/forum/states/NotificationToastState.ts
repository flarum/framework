import app from 'flarum/forum/app';
import type NotificationModel from 'flarum/common/models/Notification';

interface ToastEntry {
  id: number;
  notification: NotificationModel;
}

let nextId = 0;

/**
 * Manages the queue of realtime notification toasts.
 * Each toast auto-dismisses after the configured flarum-realtime.notification-toast-dismiss-after seconds.
 */
export default class NotificationToastState {
  private toasts: ToastEntry[] = [];

  all(): ToastEntry[] {
    return this.toasts;
  }

  push(notification: NotificationModel): void {
    const settings = (app.data?.settings ?? {}) as unknown as Record<string, number>;
    const dismissAfterS = settings['flarum-realtime.notification-toast-dismiss-after'] ?? 10;

    if (dismissAfterS === 0) return;

    const id = nextId++;

    this.toasts.push({ id, notification });
    m.redraw();

    setTimeout(() => this.dismiss(id), dismissAfterS * 1000);
  }

  dismiss(id: number): void {
    const index = this.toasts.findIndex((t) => t.id === id);

    if (index !== -1) {
      this.toasts.splice(index, 1);
      m.redraw();
    }
  }
}
