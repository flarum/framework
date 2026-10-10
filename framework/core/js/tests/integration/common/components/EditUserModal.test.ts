import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import { app } from '../../../../src/forum';
import ModalManager from '../../../../src/common/components/ModalManager';
import EditUserModal from '../../../../src/common/components/EditUserModal';
import type User from '../../../../src/common/models/User';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  app.store.pushPayload({
    data: [
      { type: 'groups', id: '1', attributes: { nameSingular: 'Admin', namePlural: 'Admins' } },
      { type: 'users', id: '1', attributes: { username: 'admin' }, relationships: { groups: { data: [{ type: 'groups', id: '1' }] } } },
      {
        type: 'users',
        id: '5',
        // Flarum 1.x could give members an all-digit username, which 2.x no
        // longer accepts as a new value.
        attributes: { username: '12345678901234567890', email: 'member@machine.local', isEmailConfirmed: false, canEditCredentials: true },
      },
    ],
  } as any);
});

beforeEach(() => app.modal.close());
afterEach(() => jest.restoreAllMocks());

const member = () => app.store.getById<User>('users', '5')!;

function open() {
  const save = jest.spyOn(member(), 'save').mockResolvedValue(member() as never);

  // The fields' `bidi` bindings aren't wired up in this environment, so tests
  // change values through the modal itself.
  let modal: any;
  const oninit = EditUserModal.prototype.oninit;
  jest.spyOn(EditUserModal.prototype, 'oninit').mockImplementation(function (this: any, vnode: any) {
    modal = this;
    oninit.call(this, vnode);
  });

  const manager = mq(ModalManager, { state: app.modal });

  app.modal.show(EditUserModal, { user: member() });
  manager.redraw();

  return { manager, modal, save };
}

describe('EditUserModal', () => {
  it('does not send the username when it was not changed', () => {
    const { manager, save } = open();

    manager.trigger('form', 'submit', {});

    expect(save).toHaveBeenCalledTimes(1);
    expect(save.mock.calls[0][0]).not.toHaveProperty('username');
    expect(save.mock.calls[0][0]).toHaveProperty('email', 'member@machine.local');
  });

  it('sends the username when it was changed', () => {
    const { manager, modal, save } = open();

    modal.username('renamed');
    manager.trigger('form', 'submit', {});

    expect(save.mock.calls[0][0]).toHaveProperty('username', 'renamed');
  });

  it('does not send the username when activating the account', () => {
    const { manager, save } = open();

    manager.click('.EditUserModal .Button--block');

    expect(save).toHaveBeenCalledTimes(1);
    expect(save.mock.calls[0][0]).toEqual({ isEmailConfirmed: true });
  });
});
