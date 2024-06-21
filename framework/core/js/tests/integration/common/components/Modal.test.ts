import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Modal from '../../../../src/common/components/Modal';
import ModalManagerState from '../../../../src/common/states/ModalManagerState';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('Modal displays as expected', () => {
  it('renders', () => {
    const modal = mq(
      m(TestModal, {
        state: new ModalManagerState(),
        animateShow: () => {},
        animateHide: () => {},
      })
    );

    expect(modal).toContainRaw('test title');
    expect(modal).toContainRaw('test content');
    expect(modal).toHaveElement('.TestClass');
  });

  it('can be dismissed via close button', () => {
    const animateHide = jest.fn();
    const modal = mq(TestModal, {
      state: new ModalManagerState(),
      animateShow: () => {},
      animateHide,
    });

    modal.click('.Modal-close button');
    expect(animateHide).toHaveBeenCalled();
  });

  it('cannot be dismissed via close button', () => {
    const modal = mq(UndismissableModal, {
      state: new ModalManagerState(),
      animateShow: () => {},
      animateHide: () => {},
    });

    expect(modal).not.toHaveElement('.Modal-close button');
  });
});

class TestModal extends Modal {
  className(): string {
    return 'TestClass';
  }

  content() {
    return 'test content';
  }

  title() {
    return 'test title';
  }
}

class UndismissableModal extends Modal {
  protected static readonly isDismissibleViaCloseButton: boolean = false;

  className(): string {
    return 'UndismissableModal';
  }

  content() {
    return 'test undismissable content';
  }

  title() {
    return 'test undismissable title';
  }
}
