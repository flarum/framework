import type NotificationModel from 'flarum/common/models/Notification';
interface ToastEntry {
    id: number;
    notification: NotificationModel;
}
/**
 * Manages the queue of realtime notification toasts.
 * Each toast auto-dismisses after the configured flarum-realtime.notification-toast-dismiss-after seconds.
 */
export default class NotificationToastState {
    private toasts;
    all(): ToastEntry[];
    push(notification: NotificationModel): void;
    dismiss(id: number): void;
}
export {};
