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
    /**
     * The id of this dialog's first or last message, when it has one.
     *
     * A dialog can be left without either, for instance when the message one of
     * them pointed at was deleted, so every caller has to cope with the
     * relationship being absent rather than reading straight through it.
     */
    messageRelationshipId(name: 'firstMessage' | 'lastMessage'): string | undefined;
    unreadCount(): number;
    lastMessageId(): number;
    lastReadMessageId(): number;
    lastReadAt(): Date;
    recipient(): User | null | undefined;
}
