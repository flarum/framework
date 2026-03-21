export default class UserMentionedNotification extends Notification<import("flarum/forum/components/Notification").INotificationAttrs> {
    constructor();
    content(): any[];
    excerpt(): string;
}
import Notification from "flarum/forum/components/Notification";
