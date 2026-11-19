import Model from 'flarum/common/Model';
import type Dialog from './Dialog';
import type User from 'flarum/common/models/User';
export default class DialogMessage extends Model {
    number(): number;
    /** 'comment' for what a member wrote; otherwise a notice, such as 'userLeft' or 'renamed'. */
    type(): string | undefined;
    /** What a notice says happened. */
    eventData(): Record<string, any>;
    isComment(): boolean;
    content(): string | null | undefined;
    contentHtml(): string | null | undefined;
    renderFailed(): boolean | undefined;
    contentPlain(): string | null | undefined;
    createdAt(): Date;
    /** When the message was last edited, or null if it never was. */
    editedAt(): Date | null | undefined;
    /** Deleted, in a way that can be undone. Only its author, and who can restore it, get its content. */
    isHidden(): boolean;
    hiddenAt(): Date | null | undefined;
    /** Only present for those allowed to see it. */
    ipAddress(): string | null | undefined;
    dialog(): false | Dialog;
    user(): false | User;
    editedUser(): false | User | null;
    hiddenUser(): false | User | null;
    /** Delete for good. */
    canDelete(): boolean;
    canEdit(): boolean | undefined;
    /** Delete in a way that can be undone, and restore. */
    canHide(): boolean | undefined;
    /** With flarum/likes: some of who liked it, this member first when they did. */
    likes(): false | (User | undefined)[];
    /** With flarum/likes. */
    likesCount(): number | undefined;
    /** With flarum/likes. */
    canLike(): boolean | undefined;
}
