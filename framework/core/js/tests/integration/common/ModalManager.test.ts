import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../src/forum';
import mq from 'mithril-query';
import m from 'mithril';
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

  describe('closing', () => {
    let root: HTMLElement;
    let manager: ModalManager;

    // Mounted in the document: the manager finds the dialog it animates there.
    beforeEach(() => {
      root = document.body.appendChild(document.createElement('div'));
      m.mount(root, { view: () => m(ModalManager, { state: app.modal, oncreate: (vnode: any) => (manager = vnode.state) }) });
    });

    afterEach(() => {
      m.mount(root, null);
      root.remove();
    });

    // jsdom runs no CSS transitions, so the end of the hide animation is sent by hand.
    const finishHideAnimation = () => {
      document.body.querySelector('.ModalManager .Modal')!.dispatchEvent(new Event('transitionend'));
      m.redraw.sync();
    };

    test('a modal closes once its hide animation ends', () => {
      app.modal.show(MyModal);
      m.redraw.sync();

      manager.animateHide();
      finishHideAnimation();

      expect(app.modal.isModalOpen()).toBe(false);
    });

    test('a modal closed some other way while animating out does not stop later modals from closing', () => {
      app.modal.show(MyModal);
      m.redraw.sync();

      // Submitting a form starts the hide animation, and the page it leads to
      // closes the modal first (Page closes any open modal), removing the
      // dialog before its transition ends.
      manager.animateHide();
      app.modal.close();
      m.redraw.sync();

      app.modal.show(MySecondModal);
      m.redraw.sync();

      manager.animateHide();
      finishHideAnimation();

      expect(app.modal.isModalOpen()).toBe(false);
    });

    test('a late end to an old hide animation does not close a newer modal', () => {
      app.modal.show(MyModal);
      m.redraw.sync();

      const oldDialog = document.body.querySelector('.ModalManager .Modal')!;

      manager.animateHide();
      app.modal.show(MySecondModal);
      m.redraw.sync();

      oldDialog.dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      expect(app.modal.isModalOpen()).toBe(true);
      expect(app.modal.modal?.componentClass).toBe(MySecondModal);

      app.modal.close();
      m.redraw.sync();
    });
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
