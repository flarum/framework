import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import Stream from 'flarum/common/utils/Stream';
import commonExtenders from '../../../../src/common/extend';
import type Dialog from '../../../../src/common/models/Dialog';
import RenameDialogModal from '../../../../src/forum/components/RenameDialogModal';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  commonExtenders.forEach((extender) => extender.extend(app, { name: 'flarum-messages', exports: {} } as any));
});

afterEach(() => jest.restoreAllMocks());

let nextId = 1100;

function group(name: string | null): Dialog {
  const id = String(nextId++);

  app.store.pushPayload({ data: { type: 'dialogs', id, attributes: { type: 'group', name, canRename: true } } } as any);

  return app.store.getById<Dialog>('dialogs', id)!;
}

/** The modal with the name typed into it, its saving and closing watched. */
function typed(dialog: Dialog, name: string) {
  const modal = Object.create(RenameDialogModal.prototype);
  modal.attrs = { dialog, onsave: jest.fn() };
  modal.name = Stream(name);
  modal.hide = jest.fn();

  const save = jest.spyOn(dialog, 'save').mockResolvedValue(dialog as never);

  return { modal, save, submit: () => modal.onsubmit({ preventDefault() {} }) };
}

describe('naming a group', () => {
  it('saves the name, trimmed, then lets the conversation know and closes', async () => {
    const { modal, save, submit } = typed(group(null), '  Ski trip ');

    await submit();

    expect(save).toHaveBeenCalledWith({ name: 'Ski trip' });
    expect(modal.attrs.onsave).toHaveBeenCalled();
    expect(modal.hide).toHaveBeenCalled();
  });

  it('takes the name away when it is emptied', async () => {
    const { save, submit } = typed(group('Ski trip'), '   ');

    await submit();

    expect(save).toHaveBeenCalledWith({ name: null });
  });

  // Nothing to tell the group.
  it('saves nothing when the name is unchanged', async () => {
    const { modal, save, submit } = typed(group('Ski trip'), 'Ski trip');

    await submit();

    expect(save).not.toHaveBeenCalled();
    expect(modal.hide).toHaveBeenCalled();
  });
});

describe('a conversation', () => {
  it('has the name the server gives it, and says whether the viewer may change it', () => {
    const dialog = group('Ski trip');

    expect(dialog.name()).toBe('Ski trip');
    expect(dialog.canRename()).toBe(true);
  });
});
