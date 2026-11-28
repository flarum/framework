import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import { app } from '../../../../src/forum';
import ConfirmModal from '../../../../src/common/components/ConfirmModal';
import Modal from '../../../../src/common/components/Modal';
import ModalManager from '../../../../src/common/components/ModalManager';
import ModalManagerState from '../../../../src/common/states/ModalManagerState';

beforeAll(() => bootstrapForum());

const until = async (condition: () => boolean) => {
  for (let i = 0; i < 100 && !condition(); i++) await new Promise((resolve) => setTimeout(resolve, 10));
};

describe('ConfirmModal', () => {
  const render = (attrs: Record<string, unknown> = {}) => {
    const onanswer = jest.fn();
    const animateHide = jest.fn();
    const modal = mq(ConfirmModal, { state: new ModalManagerState(), animateShow: () => {}, animateHide, onanswer, message: 'Delete it?', ...attrs });

    return { modal, onanswer, animateHide };
  };

  it('shows the message and names the buttons', () => {
    const { modal } = render({ confirmText: 'Delete', cancelText: 'Keep it' });

    expect(modal).toContainRaw('Delete it?');
    expect(modal).toHaveElement('.ConfirmModal-confirm');
    expect(modal.find('.ConfirmModal-confirm')[0].textContent).toBe('Delete');
    expect(modal.find('.ConfirmModal-cancel')[0].textContent).toBe('Keep it');
  });

  it('falls back to generic button labels', () => {
    const { modal } = render();

    expect(modal.find('.ConfirmModal-confirm')[0].textContent).toBe('Confirm');
    expect(modal.find('.ConfirmModal-cancel')[0].textContent).toBe('Cancel');
  });

  it('shows a heading only when given a title', () => {
    expect(render().modal).not.toHaveElement('.Modal-header');
    expect(render().modal).toHaveElement('.ConfirmModal--untitled');

    const { modal } = render({ title: 'Delete discussion' });
    expect(modal).toHaveElement('.Modal-header .ConfirmModal-title');
    expect(modal).toContainRaw('Delete discussion');
  });

  // The focus trap focuses it first; jsdom lays nothing out, so the trap itself is checked in a browser.
  it('names Cancel as the first focus, whatever the risk', () => {
    expect(render().modal).toHaveElement('.ConfirmModal-cancel[data-autofocus]');
    expect(render({ danger: true }).modal).toHaveElement('.ConfirmModal-cancel[data-autofocus]');
  });

  it('styles the confirm button by the risk of the action', () => {
    expect(render().modal).toHaveElement('.ConfirmModal-confirm.Button--primary');
    expect(render({ danger: true }).modal).toHaveElement('.ConfirmModal-confirm.Button--danger');
  });

  it('shows details under the message', () => {
    const { modal } = render({ details: m('ul', m('li', 'acme/one')) });

    expect(modal).toHaveElement('.ConfirmModal-details li');
  });

  it('answers yes once the dialog has closed', () => {
    const { modal, onanswer, animateHide } = render();

    modal.click('.ConfirmModal-confirm');
    expect(onanswer).not.toHaveBeenCalled();

    (animateHide.mock.calls[0][0] as () => void)();
    expect(onanswer).toHaveBeenCalledWith(true);
  });

  it('answers once', () => {
    const onanswer = jest.fn();
    const modal = new (ConfirmModal as any)();
    modal.attrs = { onanswer, animateHide: (callback: () => void) => callback() };

    modal.confirm();
    modal.onremove({});

    expect(onanswer).toHaveBeenCalledTimes(1);
    expect(onanswer).toHaveBeenCalledWith(true);
  });
});

describe('app.confirm', () => {
  let manager: ReturnType<typeof mq>;

  beforeAll(() => app.boot());

  beforeEach(() => {
    app.modal = new ModalManagerState();
    manager = mq(ModalManager, { state: app.modal });
  });

  const opened = async () => {
    await until(() => {
      manager.redraw();
      return manager.find('.ConfirmModal').length > 0;
    });
    expect(manager).toHaveElement('.ConfirmModal');
  };

  // The answer to a dismissal comes when Mithril removes the dialog, on a redraw.
  const answerOf = async (answer: Promise<boolean>) => {
    let result: boolean | undefined;
    answer.then((value) => (result = value));
    await until(() => {
      manager.redraw();
      return result !== undefined;
    });
    return result;
  };

  const closed = async () => {
    await until(() => {
      manager.redraw();
      return manager.find('.ConfirmModal').length === 0;
    });
    expect(manager).not.toHaveElement('.ConfirmModal');
  };

  it('resolves true when the user confirms', async () => {
    const answer = app.confirm({ message: 'Delete it?', confirmText: 'Delete' });

    await opened();
    expect(manager).toContainRaw('Delete it?');

    manager.click('.ConfirmModal-confirm');

    await expect(answer).resolves.toBe(true);
    await closed();
  });

  it('tells assistive technology what it asks', async () => {
    const answer = app.confirm({ title: 'Delete discussion', message: 'Delete it?' });

    await opened();
    const wrapper = manager.find('.ModalManager')[0];
    const describedBy = wrapper.getAttribute('aria-describedby')!;
    const labelledBy = wrapper.getAttribute('aria-labelledby')!;

    expect(wrapper.getAttribute('role')).toBe('alertdialog');
    expect(manager.find(`#${describedBy}`)[0].textContent).toBe('Delete it?');
    expect(manager.find(`#${labelledBy}`)[0].textContent).toBe('Delete discussion');

    manager.click('.ConfirmModal-cancel');
    await answerOf(answer);
  });

  it('resolves false when the user cancels', async () => {
    const answer = app.confirm({ message: 'Delete it?' });

    await opened();
    manager.click('.ConfirmModal-cancel');

    expect(await answerOf(answer)).toBe(false);
    await closed();
  });

  it('resolves false when the dialog is closed some other way', async () => {
    const answer = app.confirm({ message: 'Delete it?' });

    await opened();
    app.modal.close();

    expect(await answerOf(answer)).toBe(false);
  });

  it('resolves false when the dialog cannot be loaded', async () => {
    jest.spyOn(app.modal, 'show').mockRejectedValueOnce(new Error('ChunkLoadError'));

    await expect(app.confirm({ message: 'Delete it?' })).resolves.toBe(false);
  });

  it('opens on top of a modal, which stays open after the answer and can then close', async () => {
    await app.modal.show(EditorModal);
    await until(() => app.modal.modalList.length === 1);

    const answer = app.confirm({ message: 'Delete it?' });

    await opened();
    expect(app.modal.modalList).toHaveLength(2);

    manager.click('.ConfirmModal-confirm');
    await expect(answer).resolves.toBe(true);

    manager.redraw();
    expect(manager).toHaveElement('.EditorModal');
    expect(app.modal.modal?.componentClass).toBe(EditorModal);

    manager.click('.EditorModal .Modal-close button');
    await until(() => !app.modal.isModalOpen());
    expect(app.modal.isModalOpen()).toBe(false);
  });
});

class EditorModal extends Modal {
  className() {
    return 'EditorModal';
  }

  title() {
    return 'Edit';
  }

  content() {
    return 'Editing';
  }
}
