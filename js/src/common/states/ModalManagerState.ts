import Modal from '../components/Modal';

/**
 * Class used to manage modal state.
 *
 * Accessible on the `app` object via `app.modal` property.
 */
export default class ModalManagerState {
  /**
   * @internal
   */
  modal: null | {
    componentClass: typeof Modal;
    attrs?: Record<string, unknown>;
  };

  private closeTimeout?: number;

  constructor() {
    this.modal = null;
  }

  /**
   * Shows a modal dialog.
   *
   * If a modal is already open, the existing one will close and the new modal will replace it.
   *
   * @example <caption>Show a modal</caption>
   * app.modal.show(MyCoolModal, { attr: 'value' });
   *
   * @example <caption>Show a modal from a lifecycle method (`oncreate`, `view`, etc.)</caption>
   * // This "hack" is needed due to quirks with nested redraws in Mithril.
   * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
   */
  show(componentClass: typeof Modal, attrs: Record<string, unknown> = {}): void {
    if (!(componentClass.prototype instanceof Modal)) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      const invalidModalWarning = 'The ModalManager can only show Modals.';
      console.error(invalidModalWarning);
      throw new Error(invalidModalWarning);
    }

    clearTimeout(this.closeTimeout);

    this.modal = { componentClass, attrs };

    m.redraw.sync();
  }

  /**
   * Closes the currently open dialog, if one is open.
   */
  close(): void {
    if (!this.modal) return;

    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.closeTimeout = setTimeout(() => {
      this.modal = null;
      m.redraw();
    });
  }

  /**
   * Checks if a modal is currently open.
   *
   * @returns `true` if a modal dialog is currently open, otherwise `false`.
   */
  isModalOpen(): boolean {
    return !!this.modal;
  }
}
