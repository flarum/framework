import Component, { type ComponentAttrs } from '../../common/Component';
import type Notification from '../../common/models/Notification';
import app from '../app';

export interface INotificationTypeAttrs extends ComponentAttrs {
  notification: Notification;
}

export default class NotificationType<CustomAttrs extends INotificationTypeAttrs = INotificationTypeAttrs> extends Component<CustomAttrs> {
  view() {
    const notification = this.attrs.notification;
    const NotificationComponent = app.notificationComponents[notification.contentType()!];

    return !!NotificationComponent ? <NotificationComponent notification={notification} /> : null;
  }
}
