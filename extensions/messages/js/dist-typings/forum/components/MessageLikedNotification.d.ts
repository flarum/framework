import Notification from 'flarum/forum/components/Notification';
/** With flarum/likes: someone liked one of the member's messages. */
export default class MessageLikedNotification extends Notification {
    icon(): string;
    href(): string;
    content(): any[];
    excerpt(): string;
}
