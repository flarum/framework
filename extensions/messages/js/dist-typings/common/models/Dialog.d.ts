import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import DialogMessage from './DialogMessage';
export default class Dialog extends Model {
    title(): string;
    type(): string;
    lastMessageAt(): Date;
    createdAt(): Date;
    users(): false | (User | undefined)[];
    firstMessage(): false | DialogMessage;
    lastMessage(): false | DialogMessage;
    unreadCount(): number;
    lastReadMessageId(): number;
    lastReadAt(): Date;
    recipient(): User | null | undefined;
}
