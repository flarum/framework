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

  describe('icon image help text', () => {
    beforeAll(() => app.translator.addTranslations({ 'core.admin.edit_group.icon_image_animated_webp_text': 'Needs Imagick.' }));
    afterEach(() => delete (app.data as any).imageDriver);

    test('mentions the Imagick driver on GD', () => {
      app.data.imageDriver = 'GD';

      expect(render('4')).toContainRaw('Needs Imagick.');
    });

    test.each(['Imagick', undefined])('does not mention it on %s', (driver) => {
      (app.data as any).imageDriver = driver;

      expect(render('4')).not.toContainRaw('Needs Imagick.');
    });
  });
});
