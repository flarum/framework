import Model from 'flarum/common/Model';
import type Dialog from './Dialog';
import type User from 'flarum/common/models/User';
export default class DialogMessage extends Model {
    content(): string | null | undefined;
    contentHtml(): string | null | undefined;
    renderFailed(): boolean | undefined;
    contentPlain(): string | null | undefined;
    createdAt(): Date;
    dialog(): false | Dialog;
    user(): false | User;
}
