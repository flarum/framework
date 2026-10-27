import type Dialog from '../../common/models/Dialog';
/**
 * Keeps the member's count of unread conversations in step with a dialog
 * whose read state may just have changed.
 *
 * The count is of dialogs, not messages, so it only moves when a dialog goes
 * from unread to read or back. Taking it from the dialog's state after the
 * fact sent it negative: an already-read dialog marked read again, or a single
 * mark finishing after mark-all had already zeroed it.
 */
export declare function reconcileUnread(dialog: Dialog, wasUnread: boolean): void;
/** Marks a dialog read up to a message, keeping the member's count in step. */
export declare function markRead(dialog: Dialog, lastReadMessageId: number): Promise<void>;
