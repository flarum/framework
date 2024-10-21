import bootstrapAdmin from '@flarum/jest-config/src/boostrap/admin';
import mq from 'mithril-query';
import { app } from '../../../../src/admin';
import ModalManager from '../../../../src/common/components/ModalManager';
import CreateUserModal from '../../../../src/admin/components/CreateUserModal';
import EditCustomCssModal from '../../../../src/admin/components/EditCustomCssModal';
import EditCustomFooterModal from '../../../../src/admin/components/EditCustomFooterModal';
import EditCustomHeaderModal from '../../../../src/admin/components/EditCustomHeaderModal';
import EditGroupModal from '../../../../src/admin/components/EditGroupModal';
import LoadingModal from '../../../../src/admin/components/LoadingModal';
import ReadmeModal from '../../../../src/admin/components/ReadmeModal';

beforeAll(() => bootstrapAdmin());

describe('Modals', () => {
  beforeAll(() => app.boot());
  beforeEach(() => app.modal.close());

  test.each([
    [CreateUserModal, {}],
    [EditCustomCssModal, {}],
    [EditCustomFooterModal, {}],
    [EditCustomHeaderModal, {}],
    [EditGroupModal, {}],
    [LoadingModal, {}],
    [ReadmeModal, { extension: { id: 'flarum-test', extra: { 'flarum-extension': { title: 'Test' } } } }],
  ])('modal renders', (modal, attrs) => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(modal, attrs || {});

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });
});
