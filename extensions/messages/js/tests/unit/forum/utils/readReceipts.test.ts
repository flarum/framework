import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import Dialog from '../../../../src/common/models/Dialog';
import DialogMessage from '../../../../src/common/models/DialogMessage';
import { onReadSignal, readersOf, readStatus, refreshReceipts } from '../../../../src/forum/utils/readReceipts';

/**
 * Read receipts as the open conversation shows them: on the viewer's own
 * messages, from how far each other member has read them.
 */

const ME = '1';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;

  for (const id of ['2', '3', '4']) {
    app.store.pushObject({ type: 'users', id, attributes: { username: `user${id}` } } as any);
  }
});

afterEach(() => jest.restoreAllMocks());

let nextId = 100;

function dialog(readReceipts: unknown, type = 'group'): Dialog {
  return app.store.pushObject<Dialog>({ type: 'dialogs', id: String(nextId++), attributes: { type, readReceipts } } as any)!;
}

function message(of: Dialog, id: number, attributes: Record<string, unknown> = {}, author = ME): DialogMessage {
  return app.store.pushObject<DialogMessage>({
    type: 'dialog-messages',
    id: String(id),
    attributes: { type: 'comment', ...attributes },
    relationships: { user: { data: { type: 'users', id: author } }, dialog: { data: { type: 'dialogs', id: of.id() } } },
  } as any)!;
}

describe('readStatus', () => {
  const receipts = {
    members: 3,
    after: 0,
    readers: [
      { userId: '2', messageId: 30 },
      { userId: '3', messageId: 10 },
    ],
  };

  it('is read by those whose latest read of yours is this one or later', () => {
    const group = dialog(receipts);

    expect(readStatus(group, message(group, 10))).toEqual({ state: 'some', readCount: 2, members: 3 });
    expect(readStatus(group, message(group, 20))).toEqual({ state: 'some', readCount: 1, members: 3 });
    expect(readStatus(group, message(group, 40))).toEqual({ state: 'sent', readCount: 0, members: 3 });
    expect(readersOf(group, message(group, 10)).sort()).toEqual(['2', '3']);
    expect(readersOf(group, message(group, 20))).toEqual(['2']);
  });

  it('is read by everyone once every member sharing receipts has read it', () => {
    const group = dialog({ ...receipts, members: 2 });

    expect(readStatus(group, message(group, 10))!.state).toBe('all');
  });

  it('is only sent where nobody else is left to read it', () => {
    const empty = dialog({ members: 0, after: 0, readers: [] }, 'direct');

    expect(readStatus(empty, message(empty, 10))!.state).toBe('sent');
  });

  it('says nothing about messages sent before read receipts began', () => {
    const group = dialog({ ...receipts, after: 10 });

    expect(readStatus(group, message(group, 10))).toBeNull();
    expect(readStatus(group, message(group, 20))!.state).toBe('some');
  });

  it('says nothing about other people’s messages, notices, or deleted messages', () => {
    const group = dialog(receipts);

    expect(readStatus(group, message(group, 11, {}, '2'))).toBeNull();
    expect(readStatus(group, message(group, 12, { type: 'renamed' }))).toBeNull();
    expect(readStatus(group, message(group, 13, { isHidden: true }))).toBeNull();
  });

  it('says nothing without receipts: not loaded yet, or the viewer does not share theirs', () => {
    const notLoaded = dialog(undefined);
    const optedOut = dialog(null);

    expect(readStatus(notLoaded, message(notLoaded, 14))).toBeNull();
    expect(readStatus(optedOut, message(optedOut, 15))).toBeNull();
  });
});

describe('onReadSignal', () => {
  it('moves a member forward', () => {
    const group = dialog({ members: 2, after: 0, readers: [{ userId: '2', messageId: 10 }, { userId: '3', messageId: 20 }] });

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 });

    expect(group.readReceipts()!.readers).toEqual([
      { userId: '2', messageId: 20 },
      { userId: '3', messageId: 20 },
    ]);
    expect(readStatus(group, message(group, 20))!.state).toBe('all');
  });

  it('never moves a member back', () => {
    const group = dialog({ members: 1, after: 0, readers: [{ userId: '2', messageId: 30 }] });

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 });

    expect(group.readReceipts()!.readers).toEqual([{ userId: '2', messageId: 30 }]);
  });

  // They may have joined since, and not be in the count of members: counting
  // them as read could say everyone has, when someone counted hasn't.
  it('loads the receipts again for someone not among the readers', () => {
    const group = dialog({ members: 1, after: 0, readers: [] });
    const find = jest.spyOn(app.store, 'find').mockImplementation((() => Promise.resolve([])) as any);

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 });

    expect(group.readReceipts()!.readers).toEqual([]);
    expect(find).toHaveBeenCalledWith('dialog-messages', { filter: { dialog: group.id() }, page: { limit: 1 } });
  });

  it('asks the forum once at a time, however many first-time readers there are', () => {
    const group = dialog({ members: 3, after: 0, readers: [] });
    let answer: (value: unknown) => void = () => {};
    const find = jest.spyOn(app.store, 'find').mockImplementation((() => new Promise((resolve) => (answer = resolve))) as any);

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 });
    onReadSignal({ dialogId: group.id(), userId: '3', messageId: 20 });

    expect(find).toHaveBeenCalledTimes(1);
    answer([]);
  });

  it('keeps a read heard while the forum is being asked', async () => {
    const group = dialog({ members: 2, after: 0, readers: [{ userId: '2', messageId: 10 }] });
    let answer: (value: unknown) => void = () => {};
    jest.spyOn(app.store, 'find').mockImplementation((() => new Promise((resolve) => (answer = resolve))) as any);

    const refreshed = refreshReceipts(group);
    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 30 });

    // The forum's answer, from before that read.
    group.pushAttributes({ readReceipts: { members: 2, after: 0, readers: [{ userId: '2', messageId: 20 }] } });
    answer([]);
    await refreshed;

    expect(group.readReceipts()!.readers).toEqual([{ userId: '2', messageId: 30 }]);
  });

  it('leaves a conversation that is not open to be brought up to date when it is opened', () => {
    const group = dialog({ members: 1, after: 0, readers: [] });
    const find = jest.spyOn(app.store, 'find');

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 }, () => false);

    expect(find).not.toHaveBeenCalled();
  });

  it('leaves a conversation loaded without receipts alone', () => {
    const group = dialog(undefined);

    onReadSignal({ dialogId: group.id(), userId: '2', messageId: 20 });

    expect(group.readReceipts()).toBeUndefined();
  });
});
