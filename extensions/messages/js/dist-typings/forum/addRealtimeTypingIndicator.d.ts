/**
 * A typing indicator in a conversation, when flarum/realtime is enabled.
 * Members signal on the conversation's own private channel, which only its
 * members may join. The realtime server replaces the signal with who sent it,
 * as the socket knows them, and honours their online-status preference the
 * way it does for discussions; see TypingState for the receiving side.
 */
export default function addRealtimeTypingIndicator(): void;
