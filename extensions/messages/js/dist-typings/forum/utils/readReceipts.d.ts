import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
export type ReadState = 'sent' | 'some' | 'all';
export interface MessageReadStatus {
    state: ReadState;
    readCount: number;
    /** Null until the viewer has written something with receipts. */
    members: number | null;
}
/** Null for anything but the viewer's own comments sent since receipts began, or without receipts. */
export declare function readStatus(dialog: Dialog | null | undefined, message: DialogMessage): MessageReadStatus | null;
export declare function readersOf(dialog: Dialog | null | undefined, message: DialogMessage): string[];
/** One refresh at a time per conversation; reads heard meanwhile survive its older answer. */
export declare function refreshReceipts(dialog: Dialog): Promise<void>;
/**
 * @param isOpen A conversation not on screen is brought up to date when next opened.
 */
export declare function onReadSignal(data: any, isOpen?: (dialog: Dialog) => boolean): void;
