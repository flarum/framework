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
    // Breaking Change Compliance Warning, Remove in Beta 15.
    if (!(componentClass.prototype instanceof Modal)) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      console.error('The ModalManager can only show Modals');
      throw new Error('The ModalManager can only show Modals');
    }
    if (componentClass.init) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      console.error(
        'The componentClass parameter must be a modal class, not a modal instance. Whichever extension triggered this modal should be updated to comply with beta 14.'
      );
      throw new Error(
        'The componentClass parameter must be a modal class, not a modal instance. Whichever extension triggered this modal should be updated to comply with beta 14.'
      );
    }
    // End Change Compliance Warning, Remove in Beta 15

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
