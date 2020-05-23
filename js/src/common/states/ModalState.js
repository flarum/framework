export default class ModalState {
  constructor(modalClass, attrs = {}) {
    this.attrs = attrs;
    this.modalClass = modalClass;
  }

  getAttrs() {
    return this.attrs;
  }

  getClass() {
    return this.modalClass;
  }
}
