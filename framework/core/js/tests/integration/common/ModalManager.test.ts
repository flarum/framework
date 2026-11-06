import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
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

    test('the closed callback runs when the hide animation ends, but not when the modal was closed some other way first', () => {
      const closed = jest.fn();

      app.modal.show(MyModal);
      m.redraw.sync();

      manager.animateHide(closed);
      app.modal.close();
      m.redraw.sync();

      expect(closed).not.toHaveBeenCalled();

      app.modal.show(MySecondModal);
      m.redraw.sync();

      manager.animateHide(closed);
      finishHideAnimation();

      expect(closed).toHaveBeenCalledTimes(1);
      expect(app.modal.isModalOpen()).toBe(false);
    });

    test('a modal stacked on one that is animating out stays open, and the hidden one leaves the stack', () => {
      const dialog = (key: number) => document.body.querySelector(`.ModalManager[data-modal-key="${key}"] .Modal`)!;

      app.modal.show(MyModal);
      app.modal.show(MySecondModal, {}, true);
      m.redraw.sync();

      const [a, b] = app.modal.modalList.map((modal) => modal.key);

      // B starts animating out, and C is stacked before B's animation ends.
      manager.animateHide();
      app.modal.show(MyModal, {}, true);
      m.redraw.sync();

      const c = app.modal.modal!.key;

      dialog(b).dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      expect(app.modal.modalList.map((modal) => modal.key)).toEqual([a, c]);
      expect(app.modal.modal?.key).toBe(c);
      expect(document.body.querySelector(`.ModalManager[data-modal-key="${b}"]`)).toBeNull();

      // C closes with Escape, and A, visible again, becomes the current modal.
      document.body.dispatchEvent(new KeyboardEvent('keyup', { key: 'Escape' }));
      dialog(c).dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      expect(app.modal.modal?.key).toBe(a);
      expect(dialog(a).classList.contains('out')).toBe(false);

      // A closes with a backdrop click.
      (document.body.querySelector(`.ModalManager[data-modal-key="${a}"] .ModalManager-invisibleBackdrop`) as HTMLElement).click();
      dialog(a).dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      expect(app.modal.isModalOpen()).toBe(false);
    });

    test('a late end to an old hide animation does not end the hide of a newer modal early', () => {
      app.modal.show(MyModal);
      app.modal.show(MySecondModal, {}, true);
      m.redraw.sync();

      const [a, b] = app.modal.modalList.map((modal) => modal.key);
      const dialogB = document.body.querySelector(`.ModalManager[data-modal-key="${b}"] .Modal`)!;

      // B starts animating out, C is stacked, and C starts animating out too.
      manager.animateHide();
      app.modal.show(MySecondModal, {}, true);
      m.redraw.sync();
      const c = app.modal.modal!.key;
      manager.animateHide();

      dialogB.dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      // C is still the one closing.
      expect(app.modal.modal?.key).toBe(c);
      expect(manager['modalClosing']).toBe(true);

      document.body.querySelector(`.ModalManager[data-modal-key="${c}"] .Modal`)!.dispatchEvent(new Event('transitionend'));
      m.redraw.sync();

      expect(app.modal.modal?.key).toBe(a);

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
