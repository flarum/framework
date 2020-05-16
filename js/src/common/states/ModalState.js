import evented from '../utils/evented';

import Modal from '../components/Modal';

class ModalState {
  constructor() {
    this.clear();
  }

  /**
   * Show a modal dialog.
   *
   * @param {Modal} component
   * @public
   */
  show(modalClass, modalProps) {
    if (!(modalClass.prototype instanceof Modal)) {
      throw new Error('The ModalManager component can only show Modals');
    }

    clearTimeout(this.hideTimeout);

    this.showing = true;
    this.modalClass = modalClass;
    this.modalProps = modalProps;

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
    if (this.modalClass) {
      this.modalOnHide();
    }

    this.modalClass = null;

    this.modalProps = {};

    this.modalOnHide = () => {};

    this.modalOnReady = () => {};

    this.showing = false;

    m.lazyRedraw();
  }

  /**
   * When the modal dialog is ready to be used, tell it!
   *
   * @protected
   */
  onready() {
    if (this.modalClass && this.modalOnReady) {
      this.modalOnReady();
    }
  }
}

Object.assign(ModalState.prototype, evented);

export default ModalState;
