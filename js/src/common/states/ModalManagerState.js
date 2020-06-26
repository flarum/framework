import evented from '../utils/evented';

import Modal from '../components/Modal';

class ModalManagerState {
  constructor() {
    this.clear();
  }

  getModal() {
    return this.modal;
  }

  /**
   * Show a modal dialog.
   *
   * @param {Modal} component
   * @public
   */
  show(type, attrs) {
    const modal = { type, attrs };

    if (!(type.prototype instanceof Modal)) {
      throw new Error('The ModalManager component can only show Modals');
    }

    clearTimeout(this.hideTimeout);

    this.showing = true;
    this.modal = modal;

    m.redraw(true);

    this.trigger('show');
  }

  /**
   * Close the modal dialog.
   *
   * @public
   */
  close() {
    if (!this.showing) return;

    // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.
    this.hideTimeout = setTimeout(() => {
      this.trigger('hide');
      this.showing = false;
    });
  }

  /**
   * Clear content from the modal area.
   *
   * @protected
   */
  clear() {
    if (this.modal) {
      // Preconfigure so that close triggers properly.
      this.showing = true;
      this.close();
    }

    this.modal = null;

    this.modalOnReady = () => {};

    this.showing = false;

    this.modalDismissible = null; // Overriden in init methods of

    m.lazyRedraw();
  }

  /**
   * When the modal dialog is ready to be used, tell it!
   *
   * @protected
   */
  onready() {
    if (this.modal && this.modalOnReady) {
      this.modalOnReady();
    }
  }
}

Object.assign(ModalManagerState.prototype, evented);

export default ModalManagerState;
