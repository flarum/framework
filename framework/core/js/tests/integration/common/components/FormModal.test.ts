import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import FormModal from '../../../../src/common/components/FormModal';
import ModalManagerState from '../../../../src/common/states/ModalManagerState';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('FormModal displays as expected', () => {
  it('renders', () => {
    const modal = mq(
      m(TestFormModal, {
        state: new ModalManagerState(),
        animateShow: () => {},
        animateHide: () => {},
      })
    );

    expect(modal).toHaveElement('form');
    expect(modal).toContainRaw('test title');
    expect(modal).toContainRaw('test content');
    expect(modal).toHaveElement('.TestClass');
  });

  it('submits', () => {
    const onsubmit = jest.fn();
    const modal = mq(TestFormModal, {
      state: new ModalManagerState(),
      animateShow: () => {},
      animateHide: () => {},
      onsubmit,
    });

    // @ts-ignore
    modal.trigger('form', 'submit', {});

    expect(onsubmit).toHaveBeenCalled();
  });
});

class TestFormModal extends FormModal {
  className(): string {
    return 'TestClass';
  }

  content() {
    return 'test content';
  }

  title() {
    return 'test title';
  }

  onsubmit(e: SubmitEvent) {
    // @ts-ignore
    this.attrs.onsubmit?.(e);
  }
}
