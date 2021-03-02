import * as Mithril from 'mithril';

export interface ComponentAttrs extends Mithril.Attributes {}

/**
 * The `Component` class defines a user interface 'building block'. A component
 * generates a virtual DOM to be rendered on each redraw.
 *
 * Essentially, this is a wrapper for Mithril's components that adds several useful features:
 *
 *  - In the `oninit` and `onbeforeupdate` lifecycle hooks, we store vnode attrs in `this.attrs.
 *    This allows us to use attrs across components without having to pass the vnode to every single
 *    method.
 *  - The static `initAttrs` method allows a convenient way to provide defaults (or to otherwise modify)
 *    the attrs that have been passed into a component.
 *  - When the component is created in the DOM, we store its DOM element under `this.element`; this lets
 *    us use jQuery to modify child DOM state from internal methods via the `this.$()` method.
 *  - A convenience `component` method, which serves as an alternative to hyperscript and JSX.
 *
 * As with other Mithril components, components extending Component can be initialized
 * and nested using JSX, hyperscript, or a combination of both. The `component` method can also
 * be used.
 *
 * @example
 * return m('div', <MyComponent foo="bar"><p>Hello World</p></MyComponent>);
 *
 * @example
 * return m('div', MyComponent.component({foo: 'bar'), m('p', 'Hello World!'));
 *
 * @see https://mithril.js.org/components.html
 */
export default abstract class Component<T extends ComponentAttrs = ComponentAttrs> implements Mithril.ClassComponent<T> {
  /**
   * The root DOM element for the component.
   */
  protected element!: Element;

  /**
   * The attributes passed into the component.
   *
   * @see https://mithril.js.org/components.html#passing-data-to-components
   */
  protected attrs!: T;

  /**
   * @inheritdoc
   */
  abstract view(vnode: Mithril.Vnode<T, this>): Mithril.Children;

  /**
   * @inheritdoc
   */
  oninit(vnode: Mithril.Vnode<T, this>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  oncreate(vnode: Mithril.VnodeDOM<T, this>) {
    this.element = vnode.dom;
  }

  /**
   * @inheritdoc
   */
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
  protected $(selector) {
    const $element = $(this.element);

    return selector ? $element.find(selector) : $element;
  }

  /**
   * Convenience method to attach a component without JSX.
   * Has the same effect as calling `m(THIS_CLASS, attrs, children)`.
   *
   * @see https://mithril.js.org/hyperscript.html#mselector,-attributes,-children
   */
  static component(attrs = {}, children = null): Mithril.Vnode {
    const componentAttrs = Object.assign({}, attrs);

    return m(this as any, componentAttrs, children);
  }

  /**
   * Saves a reference to the vnode attrs after running them through initAttrs,
   * and checking for common issues.
   */
  private setAttrs(attrs: T = {} as T): void {
    (this.constructor as typeof Component).initAttrs(attrs);

    if (attrs) {
      if ('children' in attrs) {
        throw new Error(
          `[${
            (this.constructor as any).name
          }] The "children" attribute of attrs should never be used. Either pass children in as the vnode children or rename the attribute`
        );
      }

      if ('tag' in attrs) {
        throw new Error(`[${(this.constructor as any).name}] You cannot use the "tag" attribute name with Mithril 2.`);
      }
    }

    this.attrs = attrs;
  }

  /**
   * Initialize the component's attrs.
   *
   * This can be used to assign default values for missing, optional attrs.
   */
  protected static initAttrs<T>(attrs: T): void {}
}
