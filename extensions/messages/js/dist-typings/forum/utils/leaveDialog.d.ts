import type Dialog from '../../common/models/Dialog';
/**
 * Takes the member out of a group, then off every list showing it and out of
 * the store, so a message about it arriving late can't bring it back, and off
 * their unread count if it was on it. Nothing changes until the server has
 * agreed, or said they are no longer in it (left in another tab, say).
 */
export default function leaveDialog(dialog: Dialog): Promise<void>;
