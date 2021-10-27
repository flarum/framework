/**
 * An event handler factory that makes it simpler to implement data binding
 * for component event listeners.
 *
 * The handler created by this factory passes the DOM element's attribute
 * identified by the first argument to the callback (usually a bidirectional
 * Mithril stream: https://mithril.js.org/stream.html#bidirectional-bindings).
 *
 * Replaces m.withAttr for Mithril 2.0.
 * @see https://mithril.js.org/archive/v0.2.5/mithril.withAttr.html
 */
export default (key: string, cb: Function) =>
  function (this: Element) {
    cb(this.getAttribute(key) || (this as any)[key]);
  };
