import type NotificationModel from 'flarum/common/models/Notification';

const DISMISS_AFTER_MS = 5000;

interface ToastEntry {
  id: number;
  notification: NotificationModel;
}

let nextId = 0;

/**
 * Manages the queue of realtime notification toasts.
 * Each toast auto-dismisses after DISMISS_AFTER_MS milliseconds.
 */
export default class NotificationToastState {
  private toasts: ToastEntry[] = [];

  all(): ToastEntry[] {
    return this.toasts;
  }

  push(notification: NotificationModel): void {
    const id = nextId++;

    this.toasts.push({ id, notification });
    m.redraw();

    setTimeout(() => this.dismiss(id), DISMISS_AFTER_MS);
  }

  dismiss(id: number): void {
    const index = this.toasts.findIndex((t) => t.id === id);

    if (index !== -1) {
      this.toasts.splice(index, 1);
      m.redraw();
    }
  }
}
