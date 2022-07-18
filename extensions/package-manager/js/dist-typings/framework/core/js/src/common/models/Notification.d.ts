import Model from '../Model';
import User from './User';
export default class Notification extends Model {
    contentType(): string;
    content<T = unknown>(): T;
    createdAt(): Date;
    isRead(): boolean;
    user(): false | User;
    fromUser(): false | User | null;
    subject(): false | Model | null;
}
