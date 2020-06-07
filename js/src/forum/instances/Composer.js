export default class Composer {
  constructor(bodyClass, attrs = {}, preventExitCallback = () => {}) {
    this.class = bodyClass;
    this.attrs = attrs;
    this.preventExitCallback = preventExitCallback;
  }

  initialized() {
    return this.class !== null;
  }

  getClass() {
    return this.class;
  }

  getAttrs() {
    return this.attrs;
  }

  getPreventExitCallback() {
    return this.preventExitCallback;
  }

  setPreventExitCallback(callback) {
    this.preventExitCallback = callback;
  }
}
