import Modal from './Modal';
import type { IInternalModalAttrs } from './Modal';
import RequestError from '../utils/RequestError';
import Mithril from 'mithril';

export interface IFormModalAttrs extends IInternalModalAttrs {}

/**
 * The `FormModal` component displays a modal dialog, wrapped in a form.
 * Subclasses should implement the `className`, `title`, and `content` methods.
 */
export default abstract class FormModal<ModalAttrs extends IFormModalAttrs = IFormModalAttrs, CustomState = undefined> extends Modal<
  ModalAttrs,
  CustomState
> {
  wrapper(children: Mithril.Children): Mithril.Children {
    return <form onsubmit={this.onsubmit.bind(this)}>{children}</form>;
  }

  /**
   * Handle the modal form's submit event.
   */
  onsubmit(e: SubmitEvent): void {
    // ...
  }

  /**
   * Callback executed when the modal is shown and ready to be interacted with.
   *
   * @remark Focuses the first input in the modal.
   */
  onready(): void {
    this.$().find('input, select, textarea').first().trigger('focus').trigger('select');
  }

  /**
   * Shows an alert describing an error returned from the API, and gives focus to
   * the first relevant field involved in the error.
   */
  onerror(error: RequestError): void {
    this.alertAttrs = error.alert;

    m.redraw();

    if (error.status === 422 && error.response?.errors) {
      this.$('form [name=' + (error.response.errors as any[])[0].source.pointer.replace('/data/attributes/', '') + ']').trigger('select');
    } else {
      this.onready();
    }
  }
}
