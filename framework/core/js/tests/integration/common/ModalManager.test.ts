import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import { app } from '../../../src/forum';
import mq from 'mithril-query';
import ModalManager from '../../../src/common/components/ModalManager';
import Modal from '../../../src/common/components/Modal';

beforeAll(() => bootstrapForum());

describe('ModalManager', () => {
  beforeAll(() => app.boot());

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
