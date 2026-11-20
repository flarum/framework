import app from 'flarum/forum/app';
import type Dialog from '../../common/models/Dialog';
import type { DialogReadReceipts } from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';

export type ReadState = 'sent' | 'some' | 'all';

export interface MessageReadStatus {
  state: ReadState;
  readCount: number;
  /** Null until the viewer has written something with receipts. */
  members: number | null;
}

/** Per receipts object: readers sorted newest-read first, and the statuses worked out from them. */
const memo = new WeakMap<DialogReadReceipts, { readers: DialogReadReceipts['readers']; statuses: Map<number, MessageReadStatus> }>();

function memoFor(receipts: DialogReadReceipts) {
  let entry = memo.get(receipts);

  if (!entry) {
    entry = { readers: [...receipts.readers].sort((a, b) => b.messageId - a.messageId), statuses: new Map() };
    memo.set(receipts, entry);
  }

  return entry;
}

/** Binary search over readers sorted newest-read first. */
function countReadersOf(readers: DialogReadReceipts['readers'], id: number): number {
  let low = 0;
  let high = readers.length;

  while (low < high) {
    const middle = (low + high) >> 1;

    if (readers[middle].messageId >= id) low = middle + 1;
    else high = middle;
  }

  return low;
}

function receiptsFor(dialog: Dialog | null | undefined, message: DialogMessage): DialogReadReceipts | null {
  const receipts = dialog?.readReceipts();

  if (!receipts || !message.isComment() || message.isHidden() || message.user() !== app.session.user) return null;

  if (Number(message.id()) <= (receipts.after ?? 0)) return null;

  return receipts;
}

/** Null for anything but the viewer's own comments sent since receipts began, or without receipts. */
export function readStatus(dialog: Dialog | null | undefined, message: DialogMessage): MessageReadStatus | null {
  const receipts = receiptsFor(dialog, message);

  if (!receipts) return null;

  const id = Number(message.id());
  const entry = memoFor(receipts);
  let status = entry.statuses.get(id);

  if (!status) {
    const readCount = countReadersOf(entry.readers, id);
    let state: ReadState = 'sent';

    if (receipts.members && readCount >= receipts.members) state = 'all';
    else if (readCount) state = 'some';

    status = { state, readCount, members: receipts.members };
    entry.statuses.set(id, status);
  }

  return status;
}

export function readersOf(dialog: Dialog | null | undefined, message: DialogMessage): string[] {
  const receipts = receiptsFor(dialog, message);

  if (!receipts) return [];

  const readers = memoFor(receipts).readers;

  return readers.slice(0, countReadersOf(readers, Number(message.id()))).map((reader) => reader.userId);
}

/** Reads heard during a refresh, by dialog then user, re-applied to its answer. */
const pending = new Map<string, Map<string, number>>();

function moveForward(dialog: Dialog, userId: string, messageId: number): boolean {
  const receipts = dialog.readReceipts();
  const current = receipts?.readers.find((reader) => reader.userId === userId);

  if (!receipts || !current || current.messageId >= messageId) return false;

  dialog.pushAttributes({
    readReceipts: {
      ...receipts,
      readers: receipts.readers.map((reader) => (reader.userId === userId ? { userId, messageId } : reader)),
    },
  });

  return true;
}

/** One refresh at a time per conversation; reads heard meanwhile survive its older answer. */
export function refreshReceipts(dialog: Dialog): Promise<void> {
  const id = dialog.id()!;

  if (pending.has(id)) return Promise.resolve();

  pending.set(id, new Map());

  let again = false;

  return app.store
    .find('dialog-messages', { filter: { dialog: id }, page: { limit: 1 } })
    .then(() => {
      const listed = new Set((dialog.readReceipts()?.readers ?? []).map((reader) => reader.userId));

      pending.get(id)?.forEach((messageId, userId) => {
        if (!listed.has(userId)) again = true;
        else moveForward(dialog, userId, messageId);
      });
    })
    .catch(() => {})
    .finally(() => {
      pending.delete(id);
      m.redraw();

      if (again) refreshReceipts(dialog);
    });
}

/**
 * @param isOpen A conversation not on screen is brought up to date when next opened.
 */
export function onReadSignal(data: any, isOpen: (dialog: Dialog) => boolean = () => true): void {
  const dialog = data?.dialogId ? app.store.getById<Dialog>('dialogs', String(data.dialogId)) : null;
  const receipts = dialog?.readReceipts();
  const userId = data?.userId ? String(data.userId) : null;
  const messageId = Number(data?.messageId);

  if (!dialog || !receipts || !userId || !messageId) return;

  pending.get(dialog.id()!)?.set(userId, Math.max(messageId, pending.get(dialog.id()!)?.get(userId) ?? 0));

  if (receipts.readers.some((reader) => reader.userId === userId)) {
    if (moveForward(dialog, userId, messageId)) m.redraw();

    return;
  }

  // A first-time reader may have joined since the member count was taken.
  if (isOpen(dialog)) refreshReceipts(dialog);
}
