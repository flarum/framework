import Mithril, { ClassComponent, Vnode } from 'mithril';

export type ComponentProps = {
    children?: Mithril.Children;

    className?: string;

    [key: string]: any;
};

export default class Component<T extends ComponentProps = any> implements ClassComponent {
    element!: HTMLElement;

    props: T;

    constructor(props: T = <T>{}) {
        this.props = props.tag ? <T>{} : props;
    }

    view(vnode) {
        throw new Error('Component#view must be implemented by subclass');
    }

    oninit(vnode) {
        this.setProps(vnode);
    }

    oncreate(vnode) {
        this.setProps(vnode);
        this.element = vnode.dom;
    }

    onbeforeupdate(vnode) {
        this.setProps(vnode);
    }

    onupdate(vnode) {
        this.setProps(vnode);
    }

    onbeforeremove(vnode) {
        this.setProps(vnode);
    }

    onremove(vnode) {
        this.setProps(vnode);
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
     * @param selector a jQuery-compatible selector string
     * @final
     */
    $(selector?: string): ZeptoCollection {
        const $element = $(this.element);

        return selector ? $element.find(selector) : $element;
    }

    render() {
        return m(this.constructor, this.props);
    }

    static component(props: ComponentProps | any = {}, children?: Mithril.Children) {
        const componentProps: ComponentProps = Object.assign({}, props);

        if (children) componentProps.children = children;

        return m(this, componentProps);
    }

    static initProps(props: ComponentProps = {}) {}

    private setProps(vnode: Vnode<T, this>) {
        const props = vnode.attrs || {};

        (this.constructor as typeof Component).initProps(props);

        if (!props.children) props.children = vnode.children;

        this.props = props;
    }
}
