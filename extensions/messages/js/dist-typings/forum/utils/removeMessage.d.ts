import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
import type MessageStreamState from '../states/MessageStreamState';
export interface RemoveMessageOptions {
    /** The open conversation's stream, when the message may be in it. */
    state?: MessageStreamState;
    /** Whether the conversation went with the message, when the server said. */
    dialogDeleted?: boolean;
}
/**
 * Takes a message deleted for good off the screen: out of the stream, and its
 * conversation off the lists when it went with it. Otherwise, when it was the
 * conversation's latest, the lists show the one before it.
 */
export default function removeMessage(messageOrId: DialogMessage | string, dialogId: string | null, { state, dialogDeleted }?: RemoveMessageOptions): void;
/**
 * Takes a conversation that no longer exists off the lists, and off the
 * screen when it is open; one counted as unread no longer is.
 */
export declare function forgetDialog(dialog: Dialog): void;
