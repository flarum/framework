import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import Dialog from '../../../src/common/models/Dialog';
import DialogMessage from '../../../src/common/models/DialogMessage';
import DialogListState from '../../../src/forum/states/DialogListState';
import onMessageCreated from '../../../src/forum/onMessageCreated';

const ME = '1';
const THEM = '2';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;
});

beforeEach(() => {
  (app as any).dialogs = new DialogListState({});
  (app as any).dropdownDialogs = new DialogListState({});
  app.session.user!.pushAttributes({ unreadDialogCount: 0 });
});

afterEach(() => jest.restoreAllMocks());

let nextMessageId = 100;

const dialogData = (id: string, unreadCount = 0, withUsers = true) => ({
  type: 'dialogs',
  id,
  attributes: { unreadCount },
  relationships: withUsers
    ? {
        users: {
          data: [
            { type: 'users', id: ME },
            { type: 'users', id: THEM },
          ],
        },
      }
    : {},
});

/** A message as realtime sends it: the message, with its dialog (and this member's unread count) included. */
function created(dialogId: string, from: string, unreadAfter: number) {
  return {
    data: {
      type: 'dialog-messages',
      id: String(++nextMessageId),
      attributes: { content: 'Hello' },
      relationships: {
        dialog: { data: { type: 'dialogs', id: dialogId } },
        user: { data: { type: 'users', id: from } },
      },
    },
    included: [{ type: 'dialogs', id: dialogId, attributes: { unreadCount: unreadAfter } }],
  };
}

/** Puts dialogs in the store and lists them, in this order. */
function listed(state: DialogListState, ...dialogs: Array<ReturnType<typeof dialogData>>) {
  (state as any).pages = [{ number: 1, items: dialogs.map((data) => app.store.pushObject(data as any)) }];
}

const ids = (state: DialogListState) => state.getAllItems().map((dialog) => dialog.id());
const unreadDialogCount = () => app.session.user!.attribute<number>('unreadDialogCount');

describe('DialogListState.moveToTop', () => {
  it('moves a listed dialog to the top', () => {
    listed(app.dialogs, dialogData('1'), dialogData('2'), dialogData('3'));

    app.dialogs.moveToTop(app.store.getById<Dialog>('dialogs', '3')!);

    expect(ids(app.dialogs)).toEqual(['3', '1', '2']);
  });

  it('adds a dialog it didn’t list, trimmed to the limit', () => {
    listed(app.dialogs, dialogData('1'), dialogData('2'));
    app.store.pushObject(dialogData('4') as any);

    app.dialogs.moveToTop(app.store.getById<Dialog>('dialogs', '4')!, 2);

    expect(ids(app.dialogs)).toEqual(['4', '1']);
  });

  // It loads fresh, with the dialog where it belongs.
  it('leaves a list that hasn’t loaded alone', () => {
    app.dialogs.moveToTop(app.store.pushObject(dialogData('5') as any) as Dialog);

    expect(app.dialogs.hasItems()).toBe(false);
  });
});

describe('a new message', () => {
  it('brings its dialog to the top of both lists, with its last message', () => {
    listed(app.dialogs, dialogData('1'), dialogData('2'));
    listed(app.dropdownDialogs, dialogData('1'));
    const payload = created('2', THEM, 1);

    onMessageCreated(payload);

    expect(ids(app.dialogs)).toEqual(['2', '1']);
    expect(ids(app.dropdownDialogs)).toEqual(['2', '1']);
    expect(app.store.getById<Dialog>('dialogs', '2')!.lastMessage()).toBe(app.store.getById('dialog-messages', payload.data.id));
  });

  it('counts a dialog that has just become unread', () => {
    listed(app.dialogs, dialogData('1'));

    onMessageCreated(created('1', THEM, 1));

    expect(unreadDialogCount()).toBe(1);
  });

  // The count is of unread dialogs, not messages.
  it('does not count another message in a dialog already unread', () => {
    listed(app.dialogs, dialogData('1', 1));
    app.session.user!.pushAttributes({ unreadDialogCount: 1 });

    onMessageCreated(created('1', THEM, 2));

    expect(unreadDialogCount()).toBe(1);
  });

  // Deck binds the user channel globally, and global handlers run first: the
  // payload is in the store before this handler sees it, so the store already
  // says the dialog is unread.
  it('counts a dialog that became unread even when the payload was already in the store', () => {
    listed(app.dialogs, dialogData('1'));
    const payload = created('1', THEM, 1);

    app.store.pushPayload(payload);
    onMessageCreated(payload);

    expect(unreadDialogCount()).toBe(1);
  });

  it('goes by the payload, not a store that is behind', () => {
    listed(app.dialogs, dialogData('1'));
    app.session.user!.pushAttributes({ unreadDialogCount: 1 });

    onMessageCreated(created('1', THEM, 2));

    expect(unreadDialogCount()).toBe(1);
  });

  it('keeps the member’s own messages out of the unread dropdown and count', () => {
    listed(app.dialogs, dialogData('1'), dialogData('2'));
    listed(app.dropdownDialogs, dialogData('1'));

    onMessageCreated(created('2', ME, 0));

    expect(ids(app.dialogs)).toEqual(['2', '1']);
    expect(ids(app.dropdownDialogs)).toEqual(['1']);
    expect(unreadDialogCount()).toBe(0);
  });

  it('fetches a dialog this browser hasn’t seen, with its participants', async () => {
    listed(app.dialogs, dialogData('1'));
    const find = jest
      .spyOn(app.store, 'find')
      .mockImplementation(((type: string, id: string) => Promise.resolve(app.store.pushObject(dialogData(id) as any))) as any);

    onMessageCreated(created('9', THEM, 1));
    await Promise.resolve();
    await Promise.resolve();

    // As the list itself asks for dialogs, so this one arrives with the same.
    const include = app.dialogs.requestParams().include as string[];
    expect(find).toHaveBeenCalledWith('dialogs', '9', { include: include.join(',') });
    expect(ids(app.dialogs)).toEqual(['9', '1']);
  });

  // E.g. from an earlier message's payload, which carries the dialog alone.
  it('fetches a dialog it has without its participants', async () => {
    listed(app.dialogs, dialogData('1'));
    app.store.pushObject(dialogData('8', 0, false) as any);
    const find = jest
      .spyOn(app.store, 'find')
      .mockImplementation(((type: string, id: string) => Promise.resolve(app.store.pushObject(dialogData(id) as any))) as any);

    onMessageCreated(created('8', THEM, 1));
    await Promise.resolve();
    await Promise.resolve();

    expect(find).toHaveBeenCalledTimes(1);
    expect(ids(app.dialogs)).toEqual(['8', '1']);
  });

  it('ignores a payload without a dialog', () => {
    listed(app.dialogs, dialogData('1'));

    onMessageCreated({ data: { type: 'dialog-messages', id: '1', relationships: {} } });

    expect(ids(app.dialogs)).toEqual(['1']);
    expect(unreadDialogCount()).toBe(0);
  });
});
