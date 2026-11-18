import removeMessage from './utils/removeMessage';
import type MessageStreamState from './states/MessageStreamState';

interface RemovalPayload {
  data?: { type?: string; id?: string };
  meta?: { dialogId?: string | null; dialogDeleted?: boolean };
}

/**
 * A message deleted for good, as realtime sends it: only which it was, and
 * whether its conversation went with it.
 */
export default function onMessageDeleted(payload: RemovalPayload, state?: MessageStreamState): void {
  const id = payload?.data?.id;

  if (!id || payload.data?.type !== 'dialog-messages') return;

  removeMessage(id, payload.meta?.dialogId ?? null, { state, dialogDeleted: payload.meta?.dialogDeleted });
}
