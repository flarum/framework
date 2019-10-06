import Mithril from 'mithril';

export interface ComponentProps {
    oninit: Function;
    view: Function;
    component: Object;
    props: Object;
    attrs: { key: string } | null;
}

export default class Component {
    protected element: HTMLElement;

    oncreate(vnode) {
        this.element = vnode.dom;
    }

    /**
     * @param vnode 
     */
    view(vnode) {
        throw new Error('Component#view must be implemented by subclass');
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
    public $(selector?: string) {
        const $element = $(this.element);

        return selector ? $element.find(selector) : $element;
    }

    /**
     * @deprecated add component via m(Component, props) directly
     */
    public static component(props: any = {}, children?: Mithril.ChildArrayOrPrimitive) {
        const componentProps = Object.assign({}, props);

        if (children) componentProps.children = children;

        return m(this, componentProps);
    }
}
