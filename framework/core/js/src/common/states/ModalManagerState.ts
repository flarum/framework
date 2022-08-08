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

type ModalItem = {
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
  modal: ModalItem | null = null;

  /**
   * @internal
   */
  modalList: ModalItem[] = [];

  /**
   * @internal
   */
  backdropShown: boolean = false;

  /**
   * Used to force re-initialization of modals if a modal
   * is replaced by another of the same type.
   */
  private key = 0;

  /**
   * Shows a modal dialog.
   *
   * If `stackModal` is `true`, the modal will be shown on top of the current modal.
   *
   * If a value for `stackModal` is not provided, opening a new modal will close
   * any others currently being shown for backwards compatibility.
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

    this.backdropShown = true;
    m.redraw.sync();

    // We use requestAnimationFrame here, since we need to wait for the backdrop to be added
    // to the DOM before actually adding the modal to the modal list.
    //
    // This is because we use RAF inside the ModalManager onupdate lifecycle hook, and if we
    // skip this RAF call, the hook will attempt to add a focus trap as well as lock scroll
    // onto the newly added modal before it's in the DOM, creating an extra scrollbar.
    requestAnimationFrame(() => {
      // Set current modal
      this.modal = { componentClass, attrs, key: this.key++ };

      // We want to stack this modal
      if (stackModal) {
        // Remember previously opened modal and add new modal to the modal list
        this.modalList.push(this.modal);
      } else {
        // Override last modals
        this.modalList = [this.modal];
      }

      m.redraw();
    });
  }

  /**
   * Closes the topmost currently open dialog, if one is open.
   */
  close(): void {
    if (!this.modal) return;

    // If there are two modals, remove the most recent one
    if (this.modalList.length > 1) {
      // Remove last modal from list
      this.modalList.pop();

      // Open last modal from list
      this.modal = this.modalList[this.modalList.length - 1];
    } else {
      // Reset state
      this.modal = null;
      this.modalList = [];
    }

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
