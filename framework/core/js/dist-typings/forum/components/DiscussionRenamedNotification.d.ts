/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Notification from './Notification';
/**
 * The `DiscussionRenamedNotification` component displays a notification which
 * indicates that a discussion has had its title changed.
 */
export default class DiscussionRenamedNotification extends Notification {
    icon(): string;
    href(): string;
    content(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    excerpt(): null;
}
