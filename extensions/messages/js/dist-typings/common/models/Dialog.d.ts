import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import DialogMessage from './DialogMessage';
export default class Dialog extends Model {
    title(): string;
    type(): string;
    /** What members have named a group, where the forum allows it; the title already uses it. */
    name(): string | null | undefined;
    canRename(): boolean | undefined;
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
    /** Everyone in it can reply: someone allowed to message users without messaging permission has. */
    anyoneCanReply(): boolean;
    canSendMessage(): boolean;
    /** Started with several recipients at once. Still a group once members have left, however few remain. */
    isGroup(): boolean;
    /** In a direct conversation, the other member. In a group, the first of them; see recipients(). */
    recipient(): User | null | undefined;
    /** Every member but the viewer, once the members are known. */
    recipients(): User[];
}
