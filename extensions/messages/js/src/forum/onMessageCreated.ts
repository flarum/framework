import app from 'flarum/forum/app';
import type Dialog from '../common/models/Dialog';
import type DialogMessage from '../common/models/DialogMessage';

const includeOf = (include?: string | string[]): string | undefined => (Array.isArray(include) ? include.join(',') : include);

/** The dropdown shows this many unread dialogs. */
const DROPDOWN_LIMIT = 5;

/**
 * Brings a new message into every list that shows its dialog, in place: the
 * payload carries the message and its dialog (with this member's unread
 * count), so only a dialog new to this browser needs fetching, for its
 * participants.
 */
export default function onMessageCreated(data: any): void {
  const dialogId: string | undefined = data?.data?.relationships?.dialog?.data?.id;

  if (!dialogId) return;

  const known = app.store.getById<Dialog>('dialogs', dialogId);
  const wasUnread = !!known?.unreadCount();
  const message = app.store.pushPayload<DialogMessage>(data) as DialogMessage;
  const fromSelf = message.user() === app.session.user;

  const show = (dialog: Dialog) => {
    dialog.pushData({ relationships: { lastMessage: { data: { type: 'dialog-messages', id: message.id()! } } } } as any);

    app.dialogs.moveToTop(dialog);
    if (!fromSelf) app.dropdownDialogs.moveToTop(dialog, DROPDOWN_LIMIT);

    m.redraw();
  };

  if (!fromSelf && !wasUnread) {
    app.session.user!.pushAttributes({ messageCount: (app.session.user!.attribute<number>('messageCount') ?? 0) + 1 });
  }

  // A dialog this browser hasn't seen needs its participants for the list.
  if (known && known.users()) {
    show(known);
  } else {
    app.store
      // As the list asks for dialogs, so this one arrives with what the others have.
      .find<Dialog>('dialogs', dialogId, { include: includeOf(app.dialogs.requestParams().include) })
      .then(show)
      .catch(() => {});
  }
}
