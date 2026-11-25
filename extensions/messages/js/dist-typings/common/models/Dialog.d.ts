import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import type Group from 'flarum/common/models/Group';
import DialogMessage from './DialogMessage';
export interface DialogReadReceipts {
    /** Null until the viewer has written something with receipts. */
    members: number | null;
    /** Messages up to this one predate read receipts. */
    after: number;
    readers: {
        userId: string;
        messageId: number;
    }[];
}
export default class Dialog extends Model {
    title(): string;
    type(): string;
    /** What members have named a group, where the forum allows it; the title already uses it. */
    name(): string | null | undefined;
    canRename(): boolean | undefined;
    lastMessageAt(): Date;
    createdAt(): Date;
    users(): false | (User | undefined)[];
    groups(): false | (Group | undefined)[];
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
    /** Null when the viewer doesn't share theirs; loaded only with the conversation's messages. */
    readReceipts(): DialogReadReceipts | null | undefined;
    /** Only with `include=readers`. */
    readers(): false | (User | undefined)[];
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
