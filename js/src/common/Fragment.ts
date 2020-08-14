/**
 * Base class enabling jquery for mithril components attached with m.render().
 */
export default abstract class Fragment {
  element!: Element;

  /**
   * Returns a jQuery object for this component's element. If you pass in a
   * selector string, this method will return a jQuery object, using the current
   * element as its buffer.
   *
   * For example, calling `component.$('li')` will return a jQuery object
   * containing all of the `li` elements inside the DOM element of this
   * component.
   *
   * @param {String} [selector] a jQuery-compatible selector string
   * @returns {jQuery} the jQuery object for the DOM node
   * @final
   */
  $(selector) {
    const $element = $(this.element);

    return selector ? $element.find(selector) : $element;
  }

  /**
   *
   */
  render() {
    const vdom = this.view();

    vdom.attrs = vdom.attrs || {};

    const originalOnCreate = vdom.attrs.oncreate;

    vdom.attrs.oncreate = (vnode) => {
      this.element = vnode.dom;
      if (this.oncreate) this.oncreate.apply(this, vnode);
      if (originalOnCreate) originalOnCreate.apply(this, vnode);
    };

    return vdom;
  }

  oncreate: () => {};

  abstract view();
}
