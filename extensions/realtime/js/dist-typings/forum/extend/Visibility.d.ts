/**
 * Hidden, deleted and restored content, live. The server sends `removed` only
 * to members who could see the item and can't now, on their own channel; the
 * public channel's copy is for guests. Members who can still see it (e.g.
 * moderators, for a hidden post) get its new state instead.
 */
export default function Visibility(): void;
