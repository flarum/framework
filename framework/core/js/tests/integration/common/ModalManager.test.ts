import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../src/forum';
import mq from 'mithril-query';
import ModalManager from '../../../src/common/components/ModalManager';
import Modal from '../../../src/common/components/Modal';
import ModalManagerState from '../../../src/common/states/ModalManagerState';
import AlertManager from '../../../src/common/components/AlertManager';

beforeAll(() => bootstrapForum());

describe('ModalManager', () => {
  beforeAll(() => app.boot());

  test('a failed lazy modal removes the loading backdrop and shows an error', async () => {
    const state = new ModalManagerState();
    const manager = mq(ModalManager, { state });
    app.alerts.clear();
    const alerts = mq(AlertManager, { state: app.alerts });
    const error = new Error('ChunkLoadError');
    let rejectImport!: (reason: Error) => void;
    const pending = state.show(
      () =>
        new Promise((resolve, reject) => {
          rejectImport = reject;
        })
    );

    manager.redraw();
    expect(manager).toHaveElement('.Modal-backdrop .LoadingIndicator');

    const rejection = expect(pending).rejects.toBe(error);
    rejectImport(error);
    await rejection;
    manager.redraw();
    alerts.redraw();

    expect(manager).not.toHaveElement('.Modal-backdrop');
    expect(manager).not.toHaveElement('.Modal');
    expect(alerts).toHaveElement('.Alert--error');
    expect(alerts).toContainRaw('Oops! Something went wrong. Please reload the page and try again.');

    await state.show(MyModal);
    manager.redraw();
    expect(manager).toContainRaw('Hello, world!');
    state.close();
    app.alerts.clear();
  });

  test('can show and close a modal', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(MyModal);

    manager.redraw();

    expect(manager).toHaveElement('.ModalManager');
    expect(manager).toHaveElement('.ModalManager[data-modal-number="0"]');
    expect(manager).toHaveElement('.Modal');
    expect(manager).toContainRaw('Hello, world!');

    app.modal.close();

    manager.redraw();

    expect(manager).not.toHaveElement('.Modal');
    expect(manager).not.toContainRaw('Hello, world!');
  });

  test('can stack modals', () => {
    const manager = mq(ModalManager, { state: app.modal });

    app.modal.show(MyModal);
    app.modal.show(MySecondModal, {}, true);

    manager.redraw();

    expect(manager).toHaveElement('.ModalManager[data-modal-number="0"]');
    expect(manager).toHaveElement('.ModalManager[data-modal-number="1"]');
    expect(manager).toHaveElement('.Modal');
    expect(manager).toContainRaw('Hello, world!');
    expect(manager).toContainRaw('Really cool content');

    app.modal.close();

    manager.redraw();

    expect(manager).toHaveElement('.ModalManager[data-modal-number="0"]');
    expect(manager).not.toHaveElement('.ModalManager[data-modal-number="1"]');
    expect(manager).toHaveElement('.Modal');
    expect(manager).not.toContainRaw('Really cool content');
    expect(manager).toContainRaw('Hello, world!');

    app.modal.close();

    manager.redraw();

    expect(manager).not.toHaveElement('.ModalManager[data-modal-number="0"]');
    expect(manager).not.toHaveElement('.ModalManager[data-modal-number="1"]');
    expect(manager).not.toHaveElement('.Modal');
    expect(manager).not.toContainRaw('Hello, world!');
  });
});

class MyModal extends Modal {
  className(): string {
    return '';
  }

  content() {
    return 'Hello, world!';
  }

  title() {
    return 'My Modal';
  }
}

class MySecondModal extends Modal {
  className(): string {
    return '';
  }

  content() {
    return 'Really cool content';
  }

  title() {
    return 'My Second Modal';
  }
}
