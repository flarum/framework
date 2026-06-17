import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
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

describe('ModalManager lazy-load warning', () => {
  beforeAll(() => app.boot());

  let warnSpy: ReturnType<typeof jest.spyOn>;
  let forumAttributes: Record<string, unknown>;

  /** Whether any emitted warning mentions lazy loading. */
  const warnedAboutLazyLoading = (): boolean =>
    warnSpy.mock.calls.some((args) => args.some((a) => typeof a === 'string' && /lazy/i.test(a)));

  /**
   * Register a modal under a key and mark it as shipping in the main bundle, mimicking
   * what the build does for an eagerly-bundled (non-code-split) modal.
   */
  const registerInMainBundle = (key: string, modalClass: typeof Modal): void => {
    const [namespace, id] = key.split(/:(.+)/);
    flarum.reg.add(namespace, id, modalClass);
    flarum.reg.markInMainBundle(key);
  };

  beforeEach(() => {
    warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});
    // fireDebugWarning only fires when the forum is in debug mode.
    forumAttributes = (app.data.resources.find((r: any) => r.type === 'forums') as any).attributes;
    forumAttributes.debug = true;
  });

  afterEach(() => {
    warnSpy.mockRestore();
    delete forumAttributes.debug;
    app.modal.close();
  });

  test('warns when a main-bundle modal is shown eagerly', async () => {
    registerInMainBundle('core:forum/components/EagerModalA', EagerModalA);

    await app.modal.show(EagerModalA);

    expect(warnedAboutLazyLoading()).toBe(true);
  });

  test('identifies the modal by its registry key (survives minification)', async () => {
    registerInMainBundle('acme:forum/components/RegisteredModal', RegisteredModal);

    await app.modal.show(RegisteredModal);

    const namedByKey = warnSpy.mock.calls.some((args) =>
      args.some((a) => typeof a === 'string' && a.includes('acme:forum/components/RegisteredModal'))
    );

    expect(namedByKey).toBe(true);
  });

  test('does not warn when a modal is shown via a lazy import', async () => {
    await app.modal.show(() => Promise.resolve({ default: LazyModal }));

    expect(warnedAboutLazyLoading()).toBe(false);
  });

  test('does not warn for a code-split modal shown by reference (e.g. a sub-modal of a lazy chunk)', async () => {
    // The modal is registered but NOT marked as in the main bundle — its code lives only in a
    // code-split chunk. Showing it by direct reference (as a sibling modal in the same chunk
    // would) must not be flagged.
    flarum.reg.add('core', 'forum/components/ChunkedModal', ChunkedModal);

    await app.modal.show(ChunkedModal);

    expect(warnedAboutLazyLoading()).toBe(false);
  });

  test('does not warn for an unregistered modal', async () => {
    await app.modal.show(NamedModal);

    expect(warnedAboutLazyLoading()).toBe(false);
  });

  test('warns only once per modal class, however many times it is shown', async () => {
    registerInMainBundle('core:forum/components/EagerModalB', EagerModalB);

    await app.modal.show(EagerModalB);
    app.modal.close();
    await app.modal.show(EagerModalB);
    app.modal.close();
    await app.modal.show(EagerModalB);

    const lazyWarnings = warnSpy.mock.calls.filter((args) => args.some((a) => typeof a === 'string' && /lazy/i.test(a)));

    expect(lazyWarnings).toHaveLength(1);
  });

  test('does not warn when the forum is not in debug mode', async () => {
    delete forumAttributes.debug;
    registerInMainBundle('core:forum/components/EagerModalC', EagerModalC);

    await app.modal.show(EagerModalC);

    expect(warnedAboutLazyLoading()).toBe(false);
  });
});

class EagerModalA extends Modal {
  className() {
    return '';
  }
  content() {
    return 'A';
  }
  title() {
    return 'A';
  }
}

class EagerModalB extends Modal {
  className() {
    return '';
  }
  content() {
    return 'B';
  }
  title() {
    return 'B';
  }
}

class EagerModalC extends Modal {
  className() {
    return '';
  }
  content() {
    return 'C';
  }
  title() {
    return 'C';
  }
}

class LazyModal extends Modal {
  className() {
    return '';
  }
  content() {
    return 'Lazy';
  }
  title() {
    return 'Lazy';
  }
}

class NamedModal extends Modal {
  className() {
    return '';
  }
  content() {
    return 'Named';
  }
  title() {
    return 'Named';
  }
}

class RegisteredModal extends Modal {
  className() {
    return '';
  }
  content() {
    return 'Registered';
  }
  title() {
    return 'Registered';
  }
}

class ChunkedModal extends Modal {
  className() {
    return '';
  }
  content() {
    return 'Chunked';
  }
  title() {
    return 'Chunked';
  }
}

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
