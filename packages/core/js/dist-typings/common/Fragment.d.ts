import * as Mithril from 'mithril';
/**
 * The `Fragment` class represents a chunk of DOM that is rendered once with Mithril and then takes
 * over control of its own DOM and lifecycle.
 *
 * This is very similar to the `Component` wrapper class, but is used for more fine-grained control over
 * the rendering and display of some significant chunks of the DOM. In contrast to components, fragments
 * do not offer Mithril's lifecycle hooks.
 *
 * Use this when you want to enjoy the benefits of JSX / VDOM for initial rendering, combined with
 * small helper methods that then make updates to that DOM directly, instead of fully redrawing
 * everything through Mithril.
 *
 * This should only be used when necessary, and only with `m.render`. If you are unsure whether you need
 * this or `Component, you probably need `Component`.
 */
export default abstract class Fragment {
    /**
     * The root DOM element for the fragment.
     */
    protected element: Element;
    /**
     * Returns a jQuery object for this fragment's element. If you pass in a
     * selector string, this method will return a jQuery object, using the current
     * element as its buffer.
     *
     * For example, calling `fragment.$('li')` will return a jQuery object
     * containing all of the `li` elements inside the DOM element of this
     * fragment.
     *
     * @param {String} [selector] a jQuery-compatible selector string
     * @returns {jQuery} the jQuery object for the DOM node
     * @final
     */
    $(selector: any): JQuery<any>;
    /**
     * Get the renderable virtual DOM that represents the fragment's view.
     *
     * This should NOT be overridden by subclasses. Subclasses wishing to define
     * their virtual DOM should override Fragment#view instead.
     *
     * @example
     * const fragment = new MyFragment();
     * m.render(document.body, fragment.render());
     *
     * @final
     */
    render(): Mithril.Vnode<Mithril.Attributes, this>;
    /**
     * Creates a view out of virtual elements.
     */
    abstract view(): Mithril.Vnode<Mithril.Attributes, this>;
}
