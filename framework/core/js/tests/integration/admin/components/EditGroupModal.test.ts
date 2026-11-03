import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ModalManager from '../../../../src/common/components/ModalManager';
import EditGroupModal from '../../../../src/admin/components/EditGroupModal';
import Group from '../../../../src/common/models/Group';

beforeAll(() => bootstrapAdmin());

describe('EditGroupModal', () => {
  beforeAll(() => app.boot());
  beforeEach(() => app.modal.close());

  function render(id: string) {
    app.store.pushPayload({
      data: { type: 'groups', id, attributes: { nameSingular: `Group ${id}`, namePlural: `Groups ${id}`, color: null, icon: null, isHidden: false } },
    });

    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(EditGroupModal, { group: app.store.getById<Group>('groups', id) });
    manager.redraw();

    return manager;
  }

  test.each([Group.ADMINISTRATOR_ID, Group.GUEST_ID, Group.MEMBER_ID])('hides Delete for built-in group %s', (id) => {
    expect(render(id)).not.toHaveElement('.EditGroupModal-delete');
  });

  test.each(['4', '100'])('shows Delete for group %s', (id) => {
    expect(render(id)).toHaveElement('.EditGroupModal-delete');
  });
});
