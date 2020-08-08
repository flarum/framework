export default (key: string, cb: Function) =>
  function (this: Element) {
    cb(this.getAttribute(key) || this[key]);
  };
