import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import mq from 'mithril-query';
import { app } from '../../../../src/forum';
import ModalManager from '../../../../src/common/components/ModalManager';
import GlobalDiscussionsSearchSource from '../../../../src/forum/components/GlobalDiscussionsSearchSource';
import ChangeEmailModal from '../../../../src/forum/components/ChangeEmailModal';
import ChangePasswordModal from '../../../../src/forum/components/ChangePasswordModal';
import ForgotPasswordModal from '../../../../src/forum/components/ForgotPasswordModal';
import LogInModal from '../../../../src/forum/components/LogInModal';
import NewAccessTokenModal from '../../../../src/forum/components/NewAccessTokenModal';
import RenameDiscussionModal from '../../../../src/forum/components/RenameDiscussionModal';
import SearchModal from '../../../../src/common/components/SearchModal';
import SignUpModal from '../../../../src/forum/components/SignUpModal';

beforeAll(() => bootstrapForum());

describe('Modals', () => {
  beforeAll(() => app.boot());
  beforeEach(() => app.modal.close());

  test('ChangeEmailModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(ChangeEmailModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('ChangePasswordModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(ChangePasswordModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('ForgotPasswordModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(ForgotPasswordModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('LogInModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(LogInModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('NewAccessTokenModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(NewAccessTokenModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('RenameDiscussionModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(RenameDiscussionModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('SearchModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(SearchModal, { searchState: app.search.state, sources: [new GlobalDiscussionsSearchSource()] });

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });

  test('SignUpModal renders', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(SignUpModal);

    manager.redraw();

    expect(app.modal.modalList.length).toEqual(1);
    expect(manager).toHaveElement('.ModalManager');
  });
});
