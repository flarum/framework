import * as Mithril from 'mithril';

export type ComponentAttrs = {
  className?: string;

  [key: string]: any;
};

/**
 * The `Component` class defines a user interface 'building block'. A component
 * can generate a virtual DOM to be rendered on each redraw.
 *
 *
 * @example
 * return m('div', MyComponent.component({foo: 'bar'));
 *
 * @see https://mithril.js.org/components.html
 */
export default abstract class Component<T extends ComponentAttrs = any> implements Mithril.ClassComponent<T> {
  element!: Element;

  attrs: T;

  abstract view(vnode: Mithril.Vnode<T, this>): Mithril.Children;

  oninit(vnode: Mithril.Vnode<T, this>) {
    this.setAttrs(vnode.attrs);
  }

  oncreate(vnode: Mithril.VnodeDOM<T, this>) {
    this.element = vnode.dom;
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<T, this>) {
    this.setAttrs(vnode.attrs);
  }

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
   * Convenience method to attach a component without JSX.
   */
  static component(attrs = {}, children = null) {
    const componentProps = Object.assign({}, attrs);

    return m(this as any, componentProps, children);
  }

  private setAttrs(attrs: T) {
    this.initAttrs(attrs);

    if (attrs && 'children' in attrs) {
      throw new Error(
        'The "children" attribute of attrs should never be used. Either pass children in as the vnode children or rename the attribute'
      );
    }

    this.attrs = attrs;
  }

  protected initAttrs(attrs: T): T {
    return attrs;
  }
}
