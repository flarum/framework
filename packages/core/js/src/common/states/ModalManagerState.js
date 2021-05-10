import Modal from '../components/Modal';

export default class ModalManagerState {
  constructor() {
    this.modal = null;
  }

  /**
   * Show a modal dialog.
   *
   * @public
   */
  show(componentClass, attrs) {
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
   * Close the modal dialog.
   *
   * @public
   */
  close() {
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
}
