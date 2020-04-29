import Button, { ButtonProps } from './Button';

export interface LinkButtonProps extends ButtonProps {
    /**
     * Whether or not the page that this button links to is currently active.
     */
    active?: boolean;

    /**
     * The URL to link to. If the current URL `m.route()` matches this,
     * the `active` prop will automatically be set to true.
     */
    href?: string;

    oncreate?: Function;
}

/**
 * The `LinkButton` component defines a `Button` which links to a route.
 */
export default class LinkButton<T extends LinkButtonProps> extends Button<LinkButtonProps> {
    static initProps(props: LinkButtonProps) {
        props.active = this.isActive(props);
    }

    view() {
        const vdom = super.view();

        vdom.tag = m.route.Link;
        vdom.attrs.active = String(vdom.attrs.active);

        return vdom;
    }

    /**
     * Determine whether a component with the given props is 'active'.
     */
    static isActive(props: LinkButtonProps): boolean {
        return typeof props.active !== 'undefined' ? props.active : m.route.get() === props.href;
    }
}
