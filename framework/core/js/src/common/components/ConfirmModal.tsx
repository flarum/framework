import app from '../app';
import Modal, { type IInternalModalAttrs } from './Modal';
import Button from './Button';
import classList from '../utils/classList';
import type Mithril from 'mithril';

export interface ConfirmOptions {
  /** Without one, the dialog shows only the message. */
  title?: Mithril.Children;
  message: Mithril.Children;
  /** Shown under the message, such as a list of what will change. */
  details?: Mithril.Children;
  /** Name the action ("Delete", "Revoke") rather than "OK". */
  confirmText?: Mithril.Children;
  cancelText?: Mithril.Children;
  /** Styles the confirm button as destructive. */
  danger?: boolean;
}

export interface IConfirmModalAttrs extends IInternalModalAttrs, ConfirmOptions {
  onanswer: (confirmed: boolean) => void;
}

/**
 * Shown with `app.confirm()`.
 */
export default class ConfirmModal<CustomAttrs extends IConfirmModalAttrs = IConfirmModalAttrs> extends Modal<CustomAttrs> {
  protected answered = false;

  className() {
    return classList('ConfirmModal Modal--small', { 'ConfirmModal--untitled': !this.attrs.title });
  }

  title() {
    return this.attrs.title;
  }

  // Without the phone header's title control: on phones this dialog is a sheet, not a page.
  protected inner(): Mithril.Children {
    return (
      <>
        {!!this.attrs.title && (
          <div className="Modal-header">
            <h3 className="ConfirmModal-title">{this.attrs.title}</h3>
          </div>
        )}
        {this.content()}
      </>
    );
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="ConfirmModal-message">{this.attrs.message}</div>
        {!!this.attrs.details && <div className="ConfirmModal-details">{this.attrs.details}</div>}
        <div className="ConfirmModal-actions">
          <Button
            className={classList('Button ConfirmModal-confirm', this.attrs.danger ? 'Button--danger' : 'Button--primary')}
            onclick={() => this.confirm()}
          >
            {this.attrs.confirmText ?? app.translator.trans('core.lib.confirm_modal.confirm_button')}
          </Button>
          <Button className="Button ConfirmModal-cancel" data-autofocus onclick={() => this.hide()}>
            {this.attrs.cancelText ?? app.translator.trans('core.lib.confirm_modal.cancel_button')}
          </Button>
        </div>
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // The dialog role lives on the ModalManager's wrapper, which knows only the
    // modal's class: without these, a screen reader announces the focused button but not the question.
    const wrapper = this.element.closest('.ModalManager');

    if (wrapper) {
      const id = `ConfirmModal-${wrapper.getAttribute('data-modal-key')}`;
      const title = this.element.querySelector('.ConfirmModal-title');
      const message = this.element.querySelector('.ConfirmModal-message')!;

      message.id = `${id}-message`;
      wrapper.setAttribute('role', 'alertdialog');

      if (title) {
        title.id = `${id}-title`;
        wrapper.setAttribute('aria-labelledby', title.id);
        wrapper.setAttribute('aria-describedby', message.id);
      } else {
        wrapper.setAttribute('aria-labelledby', message.id);
      }
    }
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.answer(false);
  }

  /**
   * Answers once the dialog has gone, so the caller can close a modal this one
   * was stacked on: one modal can't start closing while another is.
   */
  protected confirm(): void {
    this.attrs.animateHide(() => this.answer(true));
  }

  protected answer(confirmed: boolean): void {
    if (this.answered) return;

    this.answered = true;
    this.attrs.onanswer(confirmed);
  }
}
