import app from 'flarum/forum/app';
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
export default function removeMessage(
  messageOrId: DialogMessage | string,
  dialogId: string | null,
  { state, dialogDeleted }: RemoveMessageOptions = {}
): void {
  const id = typeof messageOrId === 'string' ? messageOrId : messageOrId.id()!;
  const message = typeof messageOrId === 'string' ? app.store.getById<DialogMessage>('dialog-messages', id) : messageOrId;
  const dialog = message?.dialog() || (dialogId ? app.store.getById<Dialog>('dialogs', dialogId) : null) || null;

  if (message && state?.holds(id)) state.remove(message);
  if (message && typeof messageOrId === 'string') app.store.remove(message);

  if (dialog) {
    const held = state ? state.getAllItems().filter((item) => (item.dialog() || null)?.id() === dialog.id()) : [];
    // Without the server's word, a conversation with nothing left shown has gone.
    const gone = dialogDeleted ?? (state ? held.length === 0 : false);

    // Only a message this member hadn't read leaves them less to read.
    const unread = (dialog.unreadCount() ?? 0) > 0 && parseInt(id) > (dialog.lastReadMessageId() ?? 0);

    if (gone) {
      forgetDialog(dialog);
    } else if (String(dialog.lastMessageId()) === id || dialog.messageRelationshipId('lastMessage') === id) {
      // Only the newest messages held say which is latest now; a block further
      // back doesn't.
      const last = state?.atNewestEnd() ? held.sort((a, b) => parseInt(a.id()!) - parseInt(b.id()!)).pop() : undefined;

      if (last) {
        dialog.pushData({ relationships: { lastMessage: { data: { type: 'dialog-messages', id: last.id()! } } } } as any);
        dialog.pushAttributes({ lastMessageId: parseInt(last.id()!) });
      } else {
        refresh(dialog);
      }
    } else if (unread) {
      // What is left unread of it, and so whether it still counts as unread.
      refresh(dialog);
    }
  }

  m.redraw();
}

/**
 * Takes a conversation that no longer exists off the lists, and off the
 * screen when it is open; one counted as unread no longer is.
 */
export function forgetDialog(dialog: Dialog): void {
  app.dialogs.remove(dialog);
  app.dropdownDialogs.remove(dialog);

  const user = app.session.user;

  if (user && (dialog.unreadCount() ?? 0) > 0) {
    user.pushAttributes({ unreadDialogCount: Math.max(0, (user.attribute<number>('unreadDialogCount') ?? 1) - 1) });
  }

  const path = app.route('dialog', { id: dialog.id()! });
  const current = m.route.get() || '';

  if (current === path || current.startsWith(path + '/')) m.route.set(app.route('messages'));

  m.redraw();
}

/** The conversation as it is now; when nothing is left unread in it, the member has one fewer unread. */
function refresh(dialog: Dialog): void {
  const wasUnread = (dialog.unreadCount() ?? 0) > 0;

  app.store
    .find<Dialog>('dialogs', dialog.id()!, { include: 'lastMessage' })
    .then(() => {
      const user = app.session.user;

      if (user && wasUnread && !dialog.unreadCount()) {
        user.pushAttributes({ unreadDialogCount: Math.max(0, (user.attribute<number>('unreadDialogCount') ?? 1) - 1) });
      }

      m.redraw();
    })
    .catch(() => {});
}
