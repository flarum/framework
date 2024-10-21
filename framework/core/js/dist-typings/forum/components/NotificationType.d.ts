/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import type Notification from '../../common/models/Notification';
export interface INotificationTypeAttrs extends ComponentAttrs {
    notification: Notification;
}
export default class NotificationType<CustomAttrs extends INotificationTypeAttrs = INotificationTypeAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element | null;
}
