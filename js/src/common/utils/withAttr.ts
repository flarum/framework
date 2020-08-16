/**
 * An event handler factory that makes it simpler to implement data
 * binding for component event listeners.
 *
 * Replaces m.withAttr for mithril 2.0
 * @see https://mithril.js.org/archive/v0.2.5/mithril.withAttr.html
 */
export default (key: string, cb: Function) =>
  function (this: Element) {
    cb(this.getAttribute(key) || this[key]);
  };
