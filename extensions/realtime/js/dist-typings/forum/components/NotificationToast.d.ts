import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import NotificationToastState from '../states/NotificationToastState';
export interface INotificationToastAttrs extends ComponentAttrs {
    state: NotificationToastState;
}
/**
 * Renders the stack of realtime notification toasts in the top-right corner.
 * Each toast wraps the standard NotificationType component (same as the dropdown),
 * with the related discussion title shown as context above it.
 */
export default class NotificationToast extends Component<INotificationToastAttrs> {
    view(): Mithril.Children;
}
