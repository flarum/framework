import Model from '../Model';
import Discussion from './Discussion';
import User from './User';
export default class Post extends Model {
    number(): number;
    discussion(): Discussion;
    createdAt(): Date;
    user(): false | User;
    contentType(): string | null;
    content(): string | null | undefined;
    contentHtml(): string | null | undefined;
    renderFailed(): boolean | undefined;
    contentPlain(): string | null | undefined;
    editedAt(): Date | null | undefined;
    editedUser(): false | User | null;
    isEdited(): boolean;
    hiddenAt(): Date | null | undefined;
    hiddenUser(): false | User | null;
    isHidden(): boolean;
    canEdit(): boolean | undefined;
    canHide(): boolean | undefined;
    canDelete(): boolean | undefined;
}
