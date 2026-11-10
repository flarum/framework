import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import { clearAllBodyScrollLocks } from 'body-scroll-lock';
import { app } from '../../../src/forum';
import ModalManager from '../../../src/common/components/ModalManager';
import Modal from '../../../src/common/components/Modal';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  // jsdom lays nothing out, so the focus trap finds no visible tabbable element
  // in the modal and throws, and the page's scroll is never locked.
  jest.spyOn(Element.prototype, 'getClientRects').mockReturnValue([{ width: 1, height: 1 }] as unknown as DOMRectList);
});

afterEach(() => {
  clearAllBodyScrollLocks();
  document.body.style.paddingRight = '';
  delete (window as any).CSS;
});

/** The body's right padding once a modal has opened and locked the page's scroll. */
async function bodyPaddingWithModalOpen(): Promise<string> {
  // body-scroll-lock adds the gap to the body's computed padding, which jsdom
  // leaves empty unless it is set.
  document.body.style.paddingRight = '0px';

  const root = document.createElement('div');
  document.body.appendChild(root);
  m.mount(root, { view: () => m(ModalManager, { state: app.modal }) });

  app.modal.show(TestModal);
  m.redraw.sync();
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const padding = document.body.style.paddingRight;

  app.modal.close();
  m.mount(root, null);
  root.remove();

  return padding;
}

// jsdom's window is 1024px wide and its root has no width, so locking the
// page's scroll sees a 1024px scrollbar gap.
describe('opening a modal', () => {
  test('leaves the scrollbar gap to scrollbar-gutter where the browser supports it', async () => {
    (window as any).CSS = { supports: (property: string, value: string) => property === 'scrollbar-gutter' && value === 'stable' };

    expect(await bodyPaddingWithModalOpen()).toBe('0px');
  });

  test('pads the body for the scrollbar gap where scrollbar-gutter is not supported', async () => {
    (window as any).CSS = { supports: () => false };

    expect(await bodyPaddingWithModalOpen()).toBe('1024px');
  });
});

class TestModal extends Modal {
  className(): string {
    return '';
  }

  content() {
    return 'Hello, world!';
  }

  title() {
    return 'Test Modal';
  }
}
