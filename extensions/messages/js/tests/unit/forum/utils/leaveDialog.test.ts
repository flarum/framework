import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import Dialog from '../../../../src/common/models/Dialog';
import DialogListState from '../../../../src/forum/states/DialogListState';
import leaveDialog from '../../../../src/forum/utils/leaveDialog';

let nextId = 800;

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
});

beforeEach(() => {
  app.session.user!.pushAttributes({ unreadDialogCount: 2 });

  app.dialogs = new DialogListState({}, 1);
  app.dropdownDialogs = new DialogListState({ filter: { unread: true } }, 1, 5);
});

afterEach(() => jest.restoreAllMocks());

const dialog = (unreadCount: number) => app.store.pushObject({ type: 'dialogs', id: String(++nextId), attributes: { type: 'group', unreadCount } } as any) as Dialog;
const count = () => app.session.user!.attribute<number>('unreadDialogCount');

/** Puts dialogs on a list's first page, as if it had loaded them. */
function listing(state: DialogListState, ...dialogs: Dialog[]) {
  (state as any).pages = [{ number: 1, items: [...dialogs] }];
}

const leaving = () => jest.spyOn(app, 'request').mockImplementation((() => Promise.resolve(null)) as any);

describe('leaving a group', () => {
  it("asks the server to take the member out of it", async () => {
    const request = leaving();
    const group = dialog(0);

    await leaveDialog(group);

    expect(request).toHaveBeenCalledWith(expect.objectContaining({ method: 'POST', url: expect.stringMatching(new RegExp(`/dialogs/${group.id()}/leave$`)) }));
  });

  it('takes it off every list it was on, and leaves the others', async () => {
    leaving();
    const group = dialog(1);
    const other = dialog(0);
    listing(app.dialogs, group, other);
    listing(app.dropdownDialogs, group);

    await leaveDialog(group);

    expect(app.dialogs.getAllItems()).toEqual([other]);
    expect(app.dropdownDialogs.getAllItems()).toEqual([]);
  });

  it('counts it off the unread count when it had something unread', async () => {
    leaving();

    await leaveDialog(dialog(3));

    expect(count()).toBe(1);
  });

  it('leaves the unread count alone when it was read', async () => {
    leaving();

    await leaveDialog(dialog(0));

    expect(count()).toBe(2);
  });

  it("forgets it, so a late message about it can't bring it back", async () => {
    leaving();
    const group = dialog(0);

    await leaveDialog(group);

    expect(app.store.getById('dialogs', group.id()!)).toBeUndefined();
  });

  // Left in another tab, say: it is gone either way.
  it('takes a group the member is no longer in off the lists all the same', async () => {
    jest.spyOn(app, 'request').mockImplementation((() => Promise.reject(Object.assign(new Error('Not found'), { status: 404 }))) as any);
    const group = dialog(1);
    listing(app.dialogs, group);

    await leaveDialog(group);

    expect(app.dialogs.getAllItems()).toEqual([]);
  });

  it('changes nothing when the server refuses', async () => {
    jest.spyOn(app, 'request').mockImplementation((() => Promise.reject(new Error('refused'))) as any);
    const group = dialog(1);
    listing(app.dialogs, group);

    await expect(leaveDialog(group)).rejects.toThrow();

    expect(app.dialogs.getAllItems()).toEqual([group]);
    expect(count()).toBe(2);
  });
});
