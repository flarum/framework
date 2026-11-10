/**
 * Brings a new message into every list that shows its dialog, in place: the
 * payload carries the message and its dialog (with this member's unread
 * count), so only a dialog new to this browser needs fetching, for its
 * participants.
 */
export default function onMessageCreated(data: any): void;
