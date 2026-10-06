import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import Dialog from '../../../../src/common/models/Dialog';
import { markRead, reconcileUnread } from '../../../../src/forum/utils/readState';

/**
 * The member's unread count is of dialogs, so it moves only on a change from
 * unread to read or back. Reading it off the dialog's state after the fact
 * sent it negative.
 */

let nextId = 0;

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
});

beforeEach(() => app.session.user!.pushAttributes({ messageCount: 2 }));

afterEach(() => jest.restoreAllMocks());

const dialog = (unreadCount: number) => app.store.pushObject({ type: 'dialogs', id: String(++nextId), attributes: { unreadCount } } as any) as Dialog;
const count = () => app.session.user!.attribute<number>('messageCount');

/** The save succeeds, and the dialog comes back with this unread count, as the API sends it for the member. */
function saving(target: Dialog, unreadAfter: number) {
  return jest.spyOn(target, 'save').mockImplementation((() => {
    target.pushAttributes({ unreadCount: unreadAfter });

    return Promise.resolve(target);
  }) as any);
}

describe('reconcileUnread', () => {
  it('counts down a dialog that went from unread to read', () => {
    const target = dialog(0);

    reconcileUnread(target, true);

    expect(count()).toBe(1);
  });

  it('counts up a dialog that went from read to unread', () => {
    const target = dialog(1);

    reconcileUnread(target, false);

    expect(count()).toBe(3);
  });

  // Marking an already-read dialog read used to count it down again.
  it('leaves the count alone when the state did not change', () => {
    reconcileUnread(dialog(0), false);
    reconcileUnread(dialog(2), true);

    expect(count()).toBe(2);
  });

  it('never goes below zero', () => {
    app.session.user!.pushAttributes({ messageCount: 0 });

    reconcileUnread(dialog(0), true);

    expect(count()).toBe(0);
  });
});

describe('markRead', () => {
  it('saves the pointer and reconciles from the state before the save', async () => {
    const target = dialog(3);
    const save = saving(target, 0);

    await markRead(target, 42);

    expect(save).toHaveBeenCalledWith({ lastReadMessageId: 42 });
    expect(count()).toBe(1);
  });

  it('does not count a dialog the save left unread', async () => {
    const target = dialog(3);
    saving(target, 1);

    await markRead(target, 42);

    expect(count()).toBe(2);
  });

  it('leaves the count alone when the save fails', async () => {
    const target = dialog(3);
    jest.spyOn(target, 'save').mockRejectedValue(new Error('offline') as never);

    await expect(markRead(target, 42)).rejects.toThrow('offline');
    expect(count()).toBe(2);
  });
});
