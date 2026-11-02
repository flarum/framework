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
    /** Only present for those allowed to see it. */
    ipAddress(): string | null | undefined;
    dialog(): false | Dialog;
    user(): false | User;
    canDelete(): boolean;
}
