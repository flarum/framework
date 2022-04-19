import type NotificationModel from '../../common/models/Notification';
import Component, { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
export interface INotificationAttrs extends ComponentAttrs {
    notification: NotificationModel;
}
/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 */
export default abstract class Notification<CustomAttrs extends INotificationAttrs = INotificationAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    /**
     * Get the name of the icon that should be displayed in the notification.
     */
    abstract icon(): string;
    /**
     * Get the URL that the notification should link to.
     */
    abstract href(): string;
    /**
     * Get the content of the notification.
     */
    abstract content(): Mithril.Children;
    /**
     * Get the excerpt of the notification.
     */
    abstract excerpt(): Mithril.Children;
    /**
     * Mark the notification as read.
     */
    markAsRead(): void;
}
