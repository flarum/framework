import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import { clearAllBodyScrollLocks } from 'body-scroll-lock';
import { app } from '../../../src/forum';
import ModalManager from '../../../src/common/components/ModalManager';
import Modal from '../../../src/common/components/Modal';
import ModalManagerState from '../../../src/common/states/ModalManagerState';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  // jsdom lays nothing out, so the focus trap would find nothing visible to focus.
  jest.spyOn(Element.prototype, 'getClientRects').mockReturnValue([{ width: 1, height: 1 }] as unknown as DOMRectList);
});

const frame = () => new Promise((resolve) => requestAnimationFrame(resolve));
const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('focus in a modal', () => {
  let root: HTMLElement;
  let opener: HTMLButtonElement;
  let manager: ModalManager;

  beforeEach(() => {
    app.modal = new ModalManagerState();
    root = document.createElement('div');
    opener = document.createElement('button');
    document.body.append(root, opener);
    m.mount(root, { view: () => m(ModalManager, { state: app.modal, oncreate: (vnode: any) => (manager = vnode.state) }) });
    opener.focus();
  });

  afterEach(() => {
    app.modal.close();
    m.redraw.sync();
    m.mount(root, null);
    root.remove();
    opener.remove();
    clearAllBodyScrollLocks();
  });

  test('starts on the element the modal marks with data-autofocus, and returns to the opener', async () => {
    await app.modal.show(AutofocusModal);
    await frame();
    m.redraw.sync();
    await frame();
    await tick();

    expect(document.activeElement?.classList.contains('second')).toBe(true);

    app.modal.close();
    m.redraw.sync();
    await frame();
    await tick();

    expect(document.activeElement).toBe(opener);
  });

  test('a redraw that lands before the dialog is drawn does not leave it untrapped', async () => {
    const modal = { componentClass: AutofocusModal as any, attrs: {}, key: 99 };
    app.modal.modal = modal;
    app.modal.modalList = [modal];

    // The modal is in the state but not drawn yet.
    (manager as any).onupdate({});
    await frame();

    m.redraw.sync();
    await frame();
    await tick();

    expect(document.activeElement?.classList.contains('second')).toBe(true);
  });
});

class AutofocusModal extends Modal {
  className() {
    return 'AutofocusModal';
  }

  title() {
    return 'Title';
  }

  content() {
    return m('div', [m('button.first', 'First'), m('button.second', { 'data-autofocus': true }, 'Second')]);
  }
}
