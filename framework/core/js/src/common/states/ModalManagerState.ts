import type Component from '../Component';
import Modal, { IDismissibleOptions } from '../components/Modal';

/**
 * Ideally, `show` would take a higher-kinded generic, ala:
 *  `show<Attrs, C>(componentClass: C<Attrs>, attrs: Attrs): void`
 * Unfortunately, TypeScript does not support this:
 * https://github.com/Microsoft/TypeScript/issues/1213
 * Therefore, we have to use this ugly, messy workaround.
 */
type UnsafeModalClass = ComponentClass<any, Modal> & { get dismissibleOptions(): IDismissibleOptions; component: typeof Component.component };

type ModalItem = null | {
  componentClass: UnsafeModalClass;
  attrs?: Record<string, unknown>;
  key: number;
};

/**
 * Class used to manage modal state.
 *
 * Accessible on the `app` object via `app.modal` property.
 */
export default class ModalManagerState {
  /**
   * @internal
   */
  modal: ModalItem = null;

  /**
   * @internal
   */
  modalList: Array<ModalItem> = [];

  /**
   * Used to force re-initialization of modals if a modal
   * is replaced by another of the same type.
   */
  private key = 0;

  /**
   * Shows a modal dialog.
   *
   * If a modal is already open, the existing one will close and the new modal will replace it.
   *
   * Stacking modals is supported, the previous modal will stay behind the new modal.
   *
   * @example <caption>Show a modal</caption>
   * app.modal.show(MyCoolModal, { attr: 'value' });
   *
   * @example <caption>Show a modal from a lifecycle method (`oncreate`, `view`, etc.)</caption>
   * // This "hack" is needed due to quirks with nested redraws in Mithril.
   * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
   *
   * @example <caption>Stacking modals</caption>
   * app.modal.show(MyCoolStackedModal, { attr: 'value' }, true);
   */
  show(componentClass: UnsafeModalClass, attrs: Record<string, unknown> = {}, stackModal: boolean = false): void {
    if (!(componentClass.prototype instanceof Modal)) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      const invalidModalWarning = 'The ModalManager can only show Modals.';
      console.error(invalidModalWarning);
      throw new Error(invalidModalWarning);
    }

    // Set current modal
    this.modal = { componentClass, attrs, key: this.key++ };

    // We want to stack this modal
    if (stackModal) {
      // Remember previously opened modal and
      // add new modal to the modal list
      this.modalList.push(this.modal);
    } else {
      // Override last modals
      this.modalList = [this.modal];
    }

    m.redraw.sync();
  }

  /**
   * Closes the currently open dialog, if one is open.
   */
  close(): void {
    if (!this.modal) return;

    // If there are two modals, remove the most recent one
    if (this.modalList.length >= 2) {
      const currentModalPosition = this.modalList.indexOf(this.modal);

      // Remove last modal from list
      this.modalList.splice(currentModalPosition, 1);

      // Open last modal from list
      this.modal = this.modalList[this.modalList.length - 1];

      m.redraw();

      return;
    }

    // Reset current modal
    this.modal = null;

    // Empty modal list
    this.modalList = [];

    m.redraw();
  }

  /**
   * Checks if a modal is currently open.
   *
   * @return `true` if a modal dialog is currently open, otherwise `false`.
   */
  isModalOpen(): boolean {
    return !!this.modal;
  }
}
